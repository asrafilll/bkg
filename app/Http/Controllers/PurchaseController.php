<?php

namespace App\Http\Controllers;

use App\Actions\CreatePurchaseAction;
use App\Actions\DeletePurchaseAction;
use App\Actions\SearchBranchesAction;
use App\Http\Requests\PurchaseStoreRequest;
use App\Models\Purchase;
use App\Models\Item;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class PurchaseController extends Controller
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
                    $searchBranchesAction->execute($request->get('term'))
                );
            },
            'default' => function () use ($request) {
                $purchaseQuery = Purchase::query()
                    ->select([
                        'purchases.*',
                        'branches.name as branch_name',
                    ])
                    ->join('branches', 'purchases.branch_id', 'branches.id');

                if ($request->filled('term')) {
                    $purchaseQuery->where(function ($query) use ($request) {
                        $searchables = [
                            'purchases.purchase_number',
                            'purchases.customer_name',
                            'branches.name',
                        ];

                        foreach ($searchables as $searchable) {
                            $query->orWhere($searchable, 'LIKE', "%{$request->get('term')}%");
                        }
                    });
                }

                $filterables = [
                    'branch_id',
                ];

                foreach ($filterables as $filterable) {
                    if ($request->filled($filterable)) {
                        $purchaseQuery->where($filterable, $request->get($filterable));
                    }
                }

                $sortables = [
                    'purchase_number',
                    'created_at',
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

                $purchases = $purchaseQuery->orderBy($sort, $direction)->paginate();

                return Response::view('purchase.index', [
                    'purchases' => $purchases,
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
                    $searchBranchesAction->execute($request->get('term'))
                );
            },
            'fetch-items' => function () use ($request) {
                $items = Item::query()
                    ->where('name', 'LIKE', "%{$request->get('term')}%")
                    ->orderBy('name')
                    ->get();

                return Response::json($items);
            },
            'default' => function () {
                return Response::view('purchase.create');
            },
        ];

        return $actions[$request->get('action', 'default')]();
    }

    /**
     * @param PurchaseStoreRequest $purchaseStoreRequest
     * @param CreatePurchaseAction $createPurchaseAction
     * @return \Illuminate\Http\Response
     */
    public function store(
        PurchaseStoreRequest $purchaseStoreRequest,
        CreatePurchaseAction $createPurchaseAction
    ) {
        try {
            $createPurchaseAction->execute($purchaseStoreRequest->all());

            return Response::redirectTo('/purchases/create')
                ->with('success', __('crud.created', [
                    'resource' => 'purchase',
                ]));
        } catch (Exception $e) {
            return Response::redirectTo('/purchases/create')
                ->with('failed', $e->getMessage());
        }
    }

    /**
     * @param Purchase $purchase
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Purchase $purchase, Request $request)
    {
        $purchase->load([
            'branch',
            'purchaseLineItems',
        ]);

        $actions = [
            'print-invoice' => function () use ($purchase) {
                $pdf = Pdf::loadView('purchase.invoice', [
                    'purchase' => $purchase,
                ])->setPaper('a8');

                return $pdf->stream();
            },
            'default' => function () use ($purchase) {
                return Response::view('purchase.show', [
                    'purchase' => $purchase,
                ]);
            }
        ];

        return $actions[$request->get('action', 'default')]();
    }

    public function destroy(
        Purchase $purchase,
        DeletePurchaseAction $deletePurchaseAction
    ) {
        try {
            $deletePurchaseAction->execute($purchase);

            return Response::redirectTo('/purchases')
                ->with('success', __('crud.deleted', [
                    'resource' => 'purchase',
                ]));
        } catch (Exception $e) {
            return Response::redirectTo('/purchases')
                ->with('failed', $e->getMessage());
        }
    }
}