<?php

namespace App\Actions;

use App\Models\Branch;
use App\Models\OrderSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FetchProductSummariesAction
{
    /**
     * @param User $authenticatedUser
     * @param mixed $fromDate
     * @param mixed $toDate
     * @return array<string, mixed>
     */
    public function execute(
        User $authenticatedUser,
        $fromDate = null,
        $toDate = null
    ) {
        $fromDate = $fromDate ?: Carbon::now()->format('Y-m-d');
        $toDate = $toDate ?: Carbon::now()->format('Y-m-d');
        $productSummaries = $this->getProductSummaries($authenticatedUser, $fromDate, $toDate);
        $branchesWithOrderSources = $this->getBranchesWithOrderSources($authenticatedUser);

        return [
            'branches' => $branchesWithOrderSources,
        ] + $this->generate(
            $productSummaries,
            $branchesWithOrderSources
        );
    }

    /**
     * @param User $authenticatedUser
     * @param string $fromDate
     * @param string $toDate
     * @return Collection
     */
    private function getProductSummaries(
        User $authenticatedUser,
        $fromDate,
        $toDate
    ) {
        return Collection::make(
            DB::select("
                SELECT
                    products.id as product_id,
                    products.product_category_id as sub_product_category_id,
                    IFNULL(sub_product_categories.name, 'Uncategorized') as sub_product_category_name,
                    products.name as product_name,
                    order_summaries.branch_id,
                    order_summaries.branch_name,
                    order_summaries.order_source_id,
                    order_summaries.order_source_name,
                    IFNULL(SUM(order_summaries.quantity), 0) as total_quantity,
                    IFNULL(SUM(order_summaries.total), 0) as total_price
                FROM
                    products
                LEFT JOIN product_categories sub_product_categories ON
                    products.product_category_id = sub_product_categories.id
                JOIN (
                    SELECT
                        order_line_items.product_id,
                        order_line_items.quantity,
                        order_line_items.total,
                        orders.branch_id,
                        branches.name as branch_name,
                        orders.order_source_id,
                        order_sources.name as order_source_name
                    FROM
                        order_line_items
                    JOIN orders ON
                        order_line_items.order_id = orders.id
                    JOIN branches ON
                        orders.branch_id = branches.id
                    JOIN order_sources ON
                        orders.order_source_id = order_sources.id
                    JOIN branch_users ON
                        orders.branch_id = branch_users.branch_id
                    WHERE
                        branch_users.user_id = ?
                        AND orders.deleted_at IS NULL
                        AND DATE(orders.created_at) >= ?
                        AND DATE(orders.created_at) <= ?
                        ) as order_summaries ON
                        products.id = order_summaries.product_id
                GROUP BY
                    order_summaries.branch_id,
                    order_summaries.order_source_id,
                    products.id
                ORDER BY
                    sub_product_category_name ASC,
                    product_name ASC,
                    branch_name ASC,
                    order_source_name ASC;
            ", [
                $authenticatedUser->id,
                $fromDate,
                $toDate
            ]),
        );
    }

    /**
     * @param User $authenticatedUser
     * @return array<string, mixed>
     */
    private function getBranchesWithOrderSources(
        User $authenticatedUser,
    ) {
        /** @var EloquentCollection<Branch> */
        $branchCollection = Branch::query()
            ->select([
                'branches.*',
            ])
            ->join('branch_users', 'branches.id', 'branch_users.branch_id')
            ->where('branch_users.user_id', $authenticatedUser->id)
            ->orderBy('branches.name')
            ->get();
        /** @var EloquentCollection<OrderSource> */
        $orderSourceCollection = OrderSource::query()
            ->orderBy('name')
            ->get();

        $branches = [];

        foreach ($branchCollection as $branchItem) {
            $branches[$branchItem->id] = [
                'id' => $branchItem->id,
                'name' => $branchItem->name,
                'total_quantity' => 0,
                'idr_total_quantity' => '0',
                'total_price' => 0,
                'idr_total_price' => '0',
                'order_sources' => [],
            ];
            foreach ($orderSourceCollection as $orderSourceItem) {
                $branches[$branchItem->id]['order_sources'][$orderSourceItem->id] = [
                    'id' => $orderSourceItem->id,
                    'name' => $orderSourceItem->name,
                    'total_quantity' => 0,
                    'idr_total_quantity' => '0',
                    'total_price' => 0,
                    'idr_total_price' => '0',
                ];
            }
        }

        return $branches;
    }

    /**
     * @param Collection $productSummaries
     * @param array<int, mixed> $branchesWithOrderSources
     * @return array<string, mixed>
     */
    private function generate(
        $productSummaries,
        $branchesWithOrderSources
    ) {
        $productSummariesMap = [];
        $summary = [
            'total_quantity' => 0,
            'idr_total_quantity' => '0',
            'total_price' => 0,
            'idr_total_price' => '0',
            'branches' => $branchesWithOrderSources,
        ];
        $summaryPerProductCategory = [];

        foreach ($productSummaries as $productSummary) {
            if (!array_key_exists($productSummary->product_id, $productSummariesMap)) {
                $productSummariesMap[$productSummary->product_id] = [
                    'product_id' => $productSummary->product_id,
                    'product_name' => $productSummary->product_name,
                    'total_quantity' => 0,
                    'idr_total_quantity' => '0',
                    'total_price' => 0,
                    'idr_total_price' => '0',
                    'branches' => $branchesWithOrderSources,
                ];
            }

            if (!array_key_exists($productSummary->sub_product_category_id, $summaryPerProductCategory)) {
                $summaryPerProductCategory[$productSummary->sub_product_category_id] = [
                    'id' => $productSummary->sub_product_category_id,
                    'name' => $productSummary->sub_product_category_name,
                    'total_quantity' => 0,
                    'idr_total_quantity' => '0',
                    'total_price' => 0,
                    'idr_total_price' => '0',
                    'branches' => $branchesWithOrderSources,
                ];
            }

            if (is_null($productSummary->branch_id)) {
                continue;
            }

            $totalQuantity = intval($productSummary->total_quantity);
            $totalPrice = intval($productSummary->total_price);

            $summary['total_quantity'] += $totalQuantity;
            $summary['idr_total_quantity'] = $this->getIdrCurrency($summary['total_quantity']);
            $summary['total_price'] += $totalPrice;
            $summary['idr_total_price'] = $this->getIdrCurrency($summary['total_price']);
            $summary['branches'][$productSummary->branch_id]['total_quantity'] += $totalQuantity;
            $summary['branches'][$productSummary->branch_id]['idr_total_quantity'] = $this->getIdrCurrency(
                $summary['branches'][$productSummary->branch_id]['total_quantity']
            );
            $summary['branches'][$productSummary->branch_id]['total_price'] += $totalPrice;
            $summary['branches'][$productSummary->branch_id]['idr_total_price'] = $this->getIdrCurrency(
                $summary['branches'][$productSummary->branch_id]['total_price']
            );
            $summary['branches'][$productSummary->branch_id]['order_sources'][$productSummary->order_source_id]['total_quantity'] += $totalQuantity;
            $summary['branches'][$productSummary->branch_id]['order_sources'][$productSummary->order_source_id]['idr_total_quantity'] = $this->getIdrCurrency(
                $summary['branches'][$productSummary->branch_id]['order_sources'][$productSummary->order_source_id]['total_quantity']
            );
            $summary['branches'][$productSummary->branch_id]['order_sources'][$productSummary->order_source_id]['total_price'] += $totalPrice;
            $summary['branches'][$productSummary->branch_id]['order_sources'][$productSummary->order_source_id]['idr_total_price'] = $this->getIdrCurrency(
                $summary['branches'][$productSummary->branch_id]['order_sources'][$productSummary->order_source_id]['total_price']
            );

            $productSummariesMap[$productSummary->product_id]['total_quantity'] += $totalQuantity;
            $productSummariesMap[$productSummary->product_id]['idr_total_quantity'] = $this->getIdrCurrency(
                $productSummariesMap[$productSummary->product_id]['total_quantity']
            );
            $productSummariesMap[$productSummary->product_id]['total_price'] += $totalPrice;
            $productSummariesMap[$productSummary->product_id]['idr_total_price'] = $this->getIdrCurrency(
                $productSummariesMap[$productSummary->product_id]['total_price']
            );
            $productSummariesMap[$productSummary->product_id]['branches'][$productSummary->branch_id]['total_quantity'] += $totalQuantity;
            $productSummariesMap[$productSummary->product_id]['branches'][$productSummary->branch_id]['idr_total_quantity'] = $this->getIdrCurrency(
                $productSummariesMap[$productSummary->product_id]['branches'][$productSummary->branch_id]['total_quantity']
            );
            $productSummariesMap[$productSummary->product_id]['branches'][$productSummary->branch_id]['total_price'] += $totalPrice;
            $productSummariesMap[$productSummary->product_id]['branches'][$productSummary->branch_id]['idr_total_price'] = $this->getIdrCurrency(
                $productSummariesMap[$productSummary->product_id]['branches'][$productSummary->branch_id]['total_price']
            );
            $productSummariesMap[$productSummary->product_id]['branches'][$productSummary->branch_id]['order_sources'][$productSummary->order_source_id]['total_quantity'] = $totalQuantity;
            $productSummariesMap[$productSummary->product_id]['branches'][$productSummary->branch_id]['order_sources'][$productSummary->order_source_id]['idr_total_quantity'] = $this->getIdrCurrency(
                $totalQuantity
            );
            $productSummariesMap[$productSummary->product_id]['branches'][$productSummary->branch_id]['order_sources'][$productSummary->order_source_id]['total_price'] = $totalPrice;
            $productSummariesMap[$productSummary->product_id]['branches'][$productSummary->branch_id]['order_sources'][$productSummary->order_source_id]['idr_total_price'] = $this->getIdrCurrency(
                $totalPrice
            );

            $summaryPerProductCategory[$productSummary->sub_product_category_id]['total_quantity'] += $totalQuantity;
            $summaryPerProductCategory[$productSummary->sub_product_category_id]['idr_total_quantity'] = $this->getIdrCurrency(
                $summaryPerProductCategory[$productSummary->sub_product_category_id]['total_quantity']
            );
            $summaryPerProductCategory[$productSummary->sub_product_category_id]['total_price'] += $totalPrice;
            $summaryPerProductCategory[$productSummary->sub_product_category_id]['idr_total_price'] = $this->getIdrCurrency(
                $summaryPerProductCategory[$productSummary->sub_product_category_id]['total_price']
            );
            $summaryPerProductCategory[$productSummary->sub_product_category_id]['branches'][$productSummary->branch_id]['total_quantity'] += $totalQuantity;
            $summaryPerProductCategory[$productSummary->sub_product_category_id]['branches'][$productSummary->branch_id]['idr_total_quantity'] = $this->getIdrCurrency(
                $summaryPerProductCategory[$productSummary->sub_product_category_id]['branches'][$productSummary->branch_id]['total_quantity']
            );
            $summaryPerProductCategory[$productSummary->sub_product_category_id]['branches'][$productSummary->branch_id]['total_price'] += $totalPrice;
            $summaryPerProductCategory[$productSummary->sub_product_category_id]['branches'][$productSummary->branch_id]['idr_total_price'] = $this->getIdrCurrency(
                $summaryPerProductCategory[$productSummary->sub_product_category_id]['branches'][$productSummary->branch_id]['total_price']
            );

            $summaryPerProductCategory[$productSummary->sub_product_category_id]['branches'][$productSummary->branch_id]['order_sources'][$productSummary->order_source_id]['total_quantity'] += $totalQuantity;
            $summaryPerProductCategory[$productSummary->sub_product_category_id]['branches'][$productSummary->branch_id]['order_sources'][$productSummary->order_source_id]['idr_total_quantity'] = $this->getIdrCurrency(
                $summaryPerProductCategory[$productSummary->sub_product_category_id]['branches'][$productSummary->branch_id]['order_sources'][$productSummary->order_source_id]['total_quantity']
            );
            $summaryPerProductCategory[$productSummary->sub_product_category_id]['branches'][$productSummary->branch_id]['order_sources'][$productSummary->order_source_id]['total_price'] += $totalPrice;
            $summaryPerProductCategory[$productSummary->sub_product_category_id]['branches'][$productSummary->branch_id]['order_sources'][$productSummary->order_source_id]['idr_total_price'] = $this->getIdrCurrency(
                $summaryPerProductCategory[$productSummary->sub_product_category_id]['branches'][$productSummary->branch_id]['order_sources'][$productSummary->order_source_id]['total_price']
            );
        }

        return [
            'products' => $productSummariesMap,
            'summary' => $summary,
            'summaryPerProductCategory' => $summaryPerProductCategory,
        ];
    }

    /**
     * @param int $value
     * @return string
     */
    private function getIdrCurrency(int $value)
    {
        return number_format(
            $value,
            '0',
            ',',
            '.'
        );
    }
}
