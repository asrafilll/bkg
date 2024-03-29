<?php

namespace App\Http\Controllers;

use App\Actions\CreateManufactureProductComponentAction;
use App\Actions\DeleteManufactureProductComponentAction;
use App\Actions\SearchBranchesAction;
use App\Exports\ManufactureProductComponentLineItemsExport;
use App\Http\Requests\ManufactureProductComponentStoreRequest;
use App\Models\Branch;
use App\Models\ManufactureProductComponent;
use App\Models\ProductComponent;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;

class ManufactureProductComponentController extends Controller
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
            'export' => function () use ($request) {
                return Excel::download(
                    new ManufactureProductComponentLineItemsExport($request->all() + [
                        'user_id' => $request->user()->id,
                    ]),
                    'manufacture_product_components-' . Carbon::now()->unix() . '.xlsx'
                );
            },
            'default' => function () use ($request) {
                $manufactureProductComponentQuery = ManufactureProductComponent::query()
                    ->select([
                        'manufacture_product_components.*',
                        'branches.name as branch_name',
                    ])
                    ->join('branches', 'manufacture_product_components.branch_id', 'branches.id')
                    ->join('branch_users', 'manufacture_product_components.branch_id', 'branch_users.branch_id')
                    ->where('branch_users.user_id', $request->user()->id);

                if ($request->filled('term')) {
                    $manufactureProductComponentQuery->where(function ($query) use ($request) {
                        $searchables = [
                            'manufacture_product_components.order_number',
                            'branches.name',
                        ];

                        foreach ($searchables as $searchable) {
                            $query->orWhere($searchable, 'LIKE', "%{$request->get('term')}%");
                        }
                    });
                }

                $filterables = [
                    'manufacture_product_components.branch_id' => 'branch_id',
                ];

                foreach ($filterables as $field => $filterable) {
                    if ($request->filled($filterable)) {
                        $manufactureProductComponentQuery->where($field, $request->get($filterable));
                    }
                }

                if ($request->filled('start_created_at')) {
                    $manufactureProductComponentQuery->whereRaw('DATE(manufacture_product_components.created_at) >= ?', [
                        $request->get('start_created_at'),
                    ]);
                }

                if ($request->filled('end_created_at')) {
                    $manufactureProductComponentQuery->whereRaw('DATE(manufacture_product_components.created_at) <= ?', [
                        $request->get('end_created_at'),
                    ]);
                }

                $sortables = [
                    'order_number',
                    'created_at',
                    'total_line_items_weight',
                    'total_line_items_quantity',
                    'total_line_items_price',
                ];
                $sort = 'created_at';
                $direction = 'desc';

                if ($request->filled('sort') && in_array($request->get('sort'), $sortables)) {
                    $sort = $request->get('sort');
                }

                if ($request->filled('direction') && in_array($request->get('direction'), ['asc', 'desc'])) {
                    $direction = $request->get('direction');
                }

                $manufactureProductComponents = $manufactureProductComponentQuery->orderBy($sort, $direction)->paginate();

                return Response::view('manufacture-product-component.index', [
                    'manufactureProductComponents' => $manufactureProductComponents,
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
                    ->where('name', 'LIKE', "%{$request->get('term')}%")
                    ->orderBy('name')
                    ->get();

                return Response::json($productComponents);
            },
            'default' => function () {
                $mainBranch = Branch::query()
                    ->where('is_main', true)
                    ->first();

                return Response::view('manufacture-product-component.create', [
                    'mainBranch' => $mainBranch,
                ]);
            },
        ];

        return $actions[$request->get('action', 'default')]();
    }

    /**
     * @param ManufactureProductComponentStoreRequest $request
     * @param CreateManufactureProductComponentAction $createmanufactureProductComponentAction
     * @return \Illuminate\Http\Response
     */
    public function store(
        ManufactureProductComponentStoreRequest $manufactureProductComponentstoreRequest,
        CreateManufactureProductComponentAction $createmanufactureProductComponentAction
    ) {
        try {
            $order = $createmanufactureProductComponentAction->execute(
                $manufactureProductComponentstoreRequest->all(),
                $manufactureProductComponentstoreRequest->user()
            );

            return Response::redirectTo('/manufacture-product-components/' . $order->id)
                ->with('success', __('crud.created', [
                    'resource' => 'manufacture product component',
                ]));
        } catch (Exception $e) {
            return Response::redirectTo('/manufacture-product-components/create')
                ->with('failed', $e->getMessage());
        }
    }

    /**
     * @param ManufactureProductComponent $manufactureProductComponent
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(
        ManufactureProductComponent $manufactureProductComponent,
        Request $request
    ) {
        $manufactureProductComponent->load([
            'branch',
            'manufactureProductComponentLineItems',
            'creator',
        ]);

        abort_if(!$manufactureProductComponent->branch->hasUser($request->user()), 404);

        return Response::view('manufacture-product-component.show', [
            'manufactureProductComponent' => $manufactureProductComponent,
        ]);
    }

    /**
     * @param ManufactureProductComponent $manufactureProductComponent
     * @param DeleteManufactureProductComponentAction $deletemanufactureProductComponentAction
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(
        ManufactureProductComponent $manufactureProductComponent,
        DeleteManufactureProductComponentAction $deletemanufactureProductComponentAction,
        Request $request
    ) {
        abort_if(!$manufactureProductComponent->branch->hasUser($request->user()), 404);

        try {
            $deletemanufactureProductComponentAction->execute($manufactureProductComponent);

            return Response::redirectTo('/manufacture-product-components')
                ->with('success', __('crud.deleted', [
                    'resource' => 'manufacture product component',
                ]));
        } catch (Exception $e) {
            return Response::redirectTo('/manufacture-product-components')
                ->with('failed', $e->getMessage());
        }
    }
}
