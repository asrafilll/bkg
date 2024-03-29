<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrderAction;
use App\Actions\DeleteOrderAction;
use App\Actions\SearchBranchesAction;
use App\Actions\SearchOrderSourcesAction;
use App\Exports\OrderLineItemsExport;
use App\Http\Requests\OrderStoreRequest;
use App\Models\Order;
use App\Models\OrderLineItem;
use App\Models\Product;
use App\Models\ProductHamper;
use App\Models\Reseller;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{
    /**
     * @param Request $request
     * @param SearchBranchesAction $searchBranchesAction
     * @param SearchOrderSourcesAction $searchOrderSourcesAction
     * @return \Illuminate\Http\Response
     */
    public function index(
        Request $request,
        SearchBranchesAction $searchBranchesAction,
        SearchOrderSourcesAction $searchOrderSourcesAction
    ) {
        $actions = [
            'fetch-branches' => function () use ($request, $searchBranchesAction) {
                return Response::json(
                    $searchBranchesAction->execute(
                        $request->get('term'),
                        $request->user()
                    )
                );
            },
            'fetch-order-sources' => function () use ($request, $searchOrderSourcesAction) {
                return Response::json(
                    $searchOrderSourcesAction->execute($request->get('term'))
                );
            },
            'export' => function () use ($request) {
                return Excel::download(
                    new OrderLineItemsExport($request->all() + [
                        'user_id' => $request->user()->id,
                    ]),
                    'orders-' . Carbon::now()->unix() . '.xlsx'
                );
            },
            'default' => function () use ($request) {
                $orderQuery = Order::query()
                    ->select([
                        'orders.*',
                        'order_sources.name as order_source_name',
                        'branches.name as branch_name',
                    ])
                    ->join('order_sources', 'orders.order_source_id', 'order_sources.id')
                    ->join('branches', 'orders.branch_id', 'branches.id')
                    ->join('branch_users', 'branches.id', 'branch_users.branch_id')
                    ->where('branch_users.user_id', $request->user()->id);

                if ($request->filled('term')) {
                    $orderQuery->where(function ($query) use ($request) {
                        $searchables = [
                            'orders.order_number',
                            'branches.name',
                            'order_sources.name',
                            'orders.customer_name',
                        ];

                        foreach ($searchables as $searchable) {
                            $query->orWhere($searchable, 'LIKE', "%{$request->get('term')}%");
                        }

                        $query->orWhereExists(
                            fn ($query) => $query
                                ->selectRaw(1)
                                ->from('order_line_items')
                                ->whereColumn('order_line_items.order_id', 'orders.id')
                                ->where('order_line_items.product_name', 'LIKE', "%{$request->get('term')}%")
                        );
                    });
                }

                $filterables = [
                    'orders.branch_id' => 'branch_id',
                    'orders.order_source_id' => 'order_source_id',
                ];

                foreach ($filterables as $field => $filterable) {
                    if ($request->filled($filterable)) {
                        $orderQuery->where($field, $request->get($filterable));
                    }
                }

                if ($request->filled('start_created_at')) {
                    $orderQuery->whereRaw('DATE(orders.created_at) >= ?', [
                        $request->get('start_created_at'),
                    ]);
                }

                if ($request->filled('end_created_at')) {
                    $orderQuery->whereRaw('DATE(orders.created_at) <= ?', [
                        $request->get('end_created_at'),
                    ]);
                }

                $sortables = [
                    'order_number',
                    'created_at',
                    'percentage_discount',
                    'total_discount',
                    'total_line_items_quantity',
                    'total_line_items_price',
                    'total_price',
                ];
                $sort = 'created_at';
                $direction = 'desc';

                if ($request->filled('sort') && in_array($request->get('sort'), $sortables)) {
                    $sort = $request->get('sort');
                }

                if ($request->filled('direction') && in_array($request->get('direction'), ['asc', 'desc'])) {
                    $direction = $request->get('direction');
                }

                $orders = $orderQuery->orderBy($sort, $direction)->paginate();

                return Response::view('order.index', [
                    'orders' => $orders,
                ]);
            },
        ];

        return $actions[$request->get('action', 'default')]();
    }

    /**
     * @param Request $request
     * @param SearchBranchesAction $searchBranchesAction
     * @param SearchOrderSourcesAction $searchOrderSourcesAction
     * @return \Illuminate\Http\Response
     */
    public function create(
        Request $request,
        SearchBranchesAction $searchBranchesAction,
        SearchOrderSourcesAction $searchOrderSourcesAction
    ) {
        $actions = [
            'fetch-branches' => function () use ($request, $searchBranchesAction) {
                return Response::json(
                    $searchBranchesAction->execute(
                        $request->get('term'),
                        $request->user()
                    )
                );
            },
            'fetch-order-sources' => function () use ($request, $searchOrderSourcesAction) {
                return Response::json(
                    $searchOrderSourcesAction->execute($request->get('term'))
                );
            },
            'fetch-resellers' => function () use ($request) {
                $resellers = Reseller::query()
                    ->where('name', 'LIKE', "%{$request->get('term')}%")
                    ->orderBy('name')
                    ->get();

                return Response::json($resellers);
            },
            'fetch-products' => function () use ($request) {
                $products = Product::query()
                    ->select([
                        'products.*',
                        DB::raw('IFNULL(product_prices.price, products.price) as active_price'),
                        DB::raw("CONCAT_WS(' - ', product_categories.name, sub_product_categories.name, products.name) as formatted_name")
                    ])
                    ->join('product_inventories', 'products.id', 'product_inventories.product_id')
                    ->leftJoin('product_prices', function ($join) use ($request) {
                        $join
                            ->on('products.id', '=', 'product_prices.product_id')
                            ->where('product_prices.order_source_id', $request->get('order_source_id'))
                            ->where('product_prices.price', '>', 0);
                    })
                    ->join('product_categories as sub_product_categories', 'products.product_category_id', 'sub_product_categories.id')
                    ->join('product_categories', 'sub_product_categories.parent_id', 'product_categories.id')
                    ->where('product_inventories.branch_id', $request->get('branch_id'))
                    ->where('product_inventories.quantity', '>', 0)
                    ->whereRaw("CONCAT_WS(' - ', product_categories.name, sub_product_categories.name, products.name) LIKE ?", [
                        "%{$request->get('term')}%"
                    ])
                    ->orderBy('products.name')
                    ->get();

                return Response::json($products);
            },
            'fetch-hampers' => function () use ($request) {
                $productHampers = ProductHamper::query()
                    ->select('product_hampers.*', DB::raw('SUM(product_hamper_lines.quantity * products.price) as total_price'))
                    ->join('product_hamper_lines', 'product_hampers.id', '=', 'product_hamper_lines.product_hamper_id')
                    ->join('products', 'product_hamper_lines.product_id', '=', 'products.id')
                    ->where('product_hampers.name', 'LIKE', "%{$request->get('term')}%")
                    ->where('product_hampers.branch_id', $request->get('branch_id'))
                    ->orderBy('product_hampers.name')
                    ->groupBy('product_hampers.id')
                    ->get();

                return Response::json($productHampers);
            },
            'default' => function () {
                return Response::view('order.create');
            },
        ];

        return $actions[$request->get('action', 'default')]();
    }

    /**
     * @param OrderStoreRequest $orderStoreRequest
     * @param CreateOrderAction $createOrderAction
     * @return \Illuminate\Http\Response
     */
    public function store(
        OrderStoreRequest $orderStoreRequest,
        CreateOrderAction $createOrderAction
    ) {
        try {
            $order = $createOrderAction->execute(
                $orderStoreRequest->all(),
                $orderStoreRequest->user()
            );

            return Response::redirectTo('/orders/' . $order->id)
                ->with('success', __('crud.created', [
                    'resource' => 'order',
                ]));
        } catch (Exception $e) {
            return Response::redirectTo('/orders/create')
                ->with('failed', $e->getMessage());
        }
    }

    /**
     * @param Order $order
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order, Request $request)
    {
        $order->load([
            'branch',
            'orderSource',
            'orderLineItems',
        ]);

        abort_if(!$order->branch->hasUser($request->user()), 404);

        $actions = [
            'print-invoice' => function () use ($order) {
                $pdf = Pdf::loadView('order.invoice', [
                    'order' => $order,
                ])->setPaper('a8');

                return $pdf->stream();
            },
            'default' => function () use ($order) {
                return Response::view('order.show', [
                    'order' => $order,
                ]);
            }
        ];

        return $actions[$request->get('action', 'default')]();
    }

    /**
     * @param Order $order
     * @param DeleteOrderAction $deleteOrderAction
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(
        Order $order,
        DeleteOrderAction $deleteOrderAction,
        Request $request
    ) {
        abort_if(!$order->branch->hasUser($request->user()), 404);

        try {
            $deleteOrderAction->execute($order);

            return Response::redirectTo('/orders')
                ->with('success', __('crud.deleted', [
                    'resource' => 'order',
                ]));
        } catch (Exception $e) {
            return Response::redirectTo('/orders')
                ->with('failed', $e->getMessage());
        }
    }
}
