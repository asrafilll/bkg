<?php

namespace App\Http\Controllers;

use App\Actions\CreateManufactureProductAction;
use App\Actions\DeleteManufactureProductAction;
use App\Actions\SearchBranchesAction;
use App\Http\Requests\ManufactureProductStoreRequest;
use App\Models\Branch;
use App\Models\ManufactureProduct;
use App\Models\Product;
use App\Models\ProductComponent;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ManufactureProductController extends Controller
{
    /**
     * @param Request $request
     * @param SearchBranchesAction $searchBranchesAction
     * @return \Illuminate\Http\Response
     */
    public function index(
        Request $request,
        SearchBranchesAction $searchBranchesAction
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
            'default' => function () use ($request) {
                $manufactureProductQuery = ManufactureProduct::query()
                    ->with([
                        'lineProductComponents',
                        'lineProducts',
                    ])
                    ->select([
                        'manufacture_products.*',
                        'branches.name as branch_name',
                    ])
                    ->join('branches', 'manufacture_products.branch_id', 'branches.id')
                    ->join('branch_users', 'manufacture_products.branch_id', 'branch_users.branch_id')
                    ->where('branch_users.user_id', $request->user()->id);

                if ($request->filled('term')) {
                    $manufactureProductQuery->where(function ($query) use ($request) {
                        $searchables = [
                            'manufacture_products.order_number',
                            'branches.name',
                        ];

                        foreach ($searchables as $searchable) {
                            $query->orWhere($searchable, 'LIKE', "%{$request->get('term')}%");
                        }
                    });
                }

                $filterables = [
                    'manufacture_products.branch_id' => 'branch_id',
                ];

                foreach ($filterables as $field => $filterable) {
                    if ($request->filled($filterable)) {
                        $manufactureProductQuery->where($field, $request->get($filterable));
                    }
                }

                if ($request->filled('start_created_at')) {
                    $manufactureProductQuery->whereRaw('DATE(manufacture_products.created_at) >= ?', [
                        $request->get('start_created_at'),
                    ]);
                }

                if ($request->filled('end_created_at')) {
                    $manufactureProductQuery->whereRaw('DATE(manufacture_products.created_at) <= ?', [
                        $request->get('end_created_at'),
                    ]);
                }

                $sortables = [
                    'order_number',
                    'created_at',
                    'total_line_product_components_quantity',
                    'total_line_products_quantity',
                ];
                $sort = 'created_at';
                $direction = 'desc';

                if ($request->filled('sort') && in_array($request->get('sort'), $sortables)) {
                    $sort = $request->get('sort');
                }

                if ($request->filled('direction') && in_array($request->get('direction'), ['asc', 'desc'])) {
                    $direction = $request->get('direction');
                }

                $manufactureProducts = $manufactureProductQuery->orderBy($sort, $direction)->paginate();

                return Response::view('manufacture-product.index', [
                    'manufactureProducts' => $manufactureProducts,
                ]);
            },
        ];

        return $actions[$request->get('action', 'default')]();
    }

    /**
     * @param Request $request
     * @param SearchBranchesAction $searchBranchesAction
     * @return \Illuminate\Http\Response
     */
    public function create(
        Request $request,
        SearchBranchesAction $searchBranchesAction
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
            'fetch-product-components' => function () use ($request) {
                $productComponents = ProductComponent::query()
                    ->select([
                        'product_components.*',
                    ])
                    ->join('product_component_inventories', 'product_components.id', 'product_component_inventories.product_component_id')
                    ->where('product_component_inventories.branch_id', $request->get('branch_id'))
                    ->where('product_components.name', 'LIKE', "%{$request->get('term')}%")
                    ->orderBy('name')
                    ->get();

                return Response::json($productComponents);
            },
            'fetch-products' => function () use ($request) {
                $products = Product::query()
                    ->where('name', 'LIKE', "%{$request->get('term')}%")
                    ->orderBy('name')
                    ->get();

                return Response::json($products);
            },
            'default' => function () {
                $mainBranch = Branch::query()
                    ->where('is_main', true)
                    ->first();

                return Response::view('manufacture-product.create', [
                    'mainBranch' => $mainBranch,
                ]);
            },
        ];

        return $actions[$request->get('action', 'default')]();
    }

    /**
     * @param ManufactureProductStoreRequest $manufactureProductStoreRequest
     * @param CreateManufactureProductAction $createManufactureProductAction
     * @return \Illuminate\Http\Response
     */
    public function store(
        ManufactureProductStoreRequest $manufactureProductStoreRequest,
        CreateManufactureProductAction $createManufactureProductAction
    ) {
        try {
            $manufactureOrder = $createManufactureProductAction->execute(
                $manufactureProductStoreRequest->all(),
                $manufactureProductStoreRequest->user()
            );

            return Response::redirectTo('/manufacture-products/' . $manufactureOrder->id)
                ->with('success', __('crud.created', [
                    'resource' => 'manufacture product',
                ]));
        } catch (Exception $e) {
            return Response::redirectTo('/manufacture-products/create')
                ->with('failed', $e->getMessage());
        }
    }

    /**
     * @param ManufactureProduct $manufactureProduct
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(ManufactureProduct $manufactureProduct, Request $request)
    {
        $manufactureProduct->load([
            'branch',
            'lineProductComponents',
            'lineProducts',
            'creator',
        ]);

        abort_if(!$manufactureProduct->branch->hasUser($request->user()), 404);

        return Response::view('manufacture-product.show', [
            'manufactureProduct' => $manufactureProduct,
        ]);
    }

    /**
     * @param ManufactureProduct $manufactureProduct
     * @param DeleteManufactureProductAction $deletemanufactureProductAction
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(
        ManufactureProduct $manufactureProduct,
        DeleteManufactureProductAction $deleteManufactureProductAction,
        Request $request
    ) {
        abort_if(!$manufactureProduct->branch->hasUser($request->user()), 404);

        try {
            $deleteManufactureProductAction->execute($manufactureProduct);

            return Response::redirectTo('/manufacture-products')
                ->with('success', __('crud.deleted', [
                    'resource' => 'manufacture product',
                ]));
        } catch (Exception $e) {
            return Response::redirectTo('/manufacture-products')
                ->with('failed', $e->getMessage());
        }
    }
}
