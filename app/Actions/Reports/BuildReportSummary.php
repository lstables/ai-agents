<?php

namespace App\Actions\Reports;

use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\SalesOrder;
use Illuminate\Support\Carbon;

class BuildReportSummary
{
    private const TOP_N = 5;

    /**
     * Build the full report summary. Cancelled orders are excluded from
     * every money total (spend, revenue, top-N rankings) but still
     * counted in the status breakdowns, so cancellations remain visible
     * without inflating financial figures.
     *
     * @return array<string, mixed>
     */
    public function handle(?string $from, ?string $to): array
    {
        return [
            'purchasing' => $this->purchasingSummary($from, $to),
            'sales' => $this->salesSummary($from, $to),
            'inventory' => $this->inventorySummary(),
            'top_suppliers' => $this->topSuppliers($from, $to),
            'top_customers' => $this->topCustomers($from, $to),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function purchasingSummary(?string $from, ?string $to): array
    {
        $query = $this->withDateRange(Purchase::query(), $from, $to);

        $byStatus = (clone $query)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $totals = (clone $query)
            ->where('status', '!=', Purchase::STATUS_CANCELLED)
            ->selectRaw('count(*) as total_orders, coalesce(sum(total_amount), 0) as total_spend')
            ->first();

        return [
            'total_orders' => (int) $totals->total_orders,
            'total_spend' => round((float) $totals->total_spend, 2),
            'by_status' => $this->fillStatuses(Purchase::statuses(), $byStatus),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function salesSummary(?string $from, ?string $to): array
    {
        $query = $this->withDateRange(SalesOrder::query(), $from, $to);

        $byStatus = (clone $query)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $totals = (clone $query)
            ->where('status', '!=', SalesOrder::STATUS_CANCELLED)
            ->selectRaw('count(*) as total_orders, coalesce(sum(total_amount), 0) as total_revenue')
            ->first();

        return [
            'total_orders' => (int) $totals->total_orders,
            'total_revenue' => round((float) $totals->total_revenue, 2),
            'by_status' => $this->fillStatuses(SalesOrder::statuses(), $byStatus),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function inventorySummary(): array
    {
        $items = InventoryItem::query()
            ->belowReorderLevel()
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'quantity_on_hand', 'reorder_level']);

        return [
            'below_reorder_level_count' => $items->count(),
            'items' => $items->map(fn (InventoryItem $item) => [
                'id' => $item->id,
                'sku' => $item->sku,
                'name' => $item->name,
                'quantity_on_hand' => $item->quantity_on_hand,
                'reorder_level' => $item->reorder_level,
            ])->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function topSuppliers(?string $from, ?string $to): array
    {
        $query = $this->withDateRange(Purchase::query(), $from, $to)
            ->where('status', '!=', Purchase::STATUS_CANCELLED)
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->selectRaw('suppliers.id as supplier_id, suppliers.name as supplier_name, sum(purchases.total_amount) as total_spend')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total_spend')
            ->limit(self::TOP_N)
            ->get();

        return $query->map(fn ($row) => [
            'supplier' => ['id' => $row->supplier_id, 'name' => $row->supplier_name],
            'total_spend' => round((float) $row->total_spend, 2),
        ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function topCustomers(?string $from, ?string $to): array
    {
        $query = $this->withDateRange(SalesOrder::query(), $from, $to)
            ->where('status', '!=', SalesOrder::STATUS_CANCELLED)
            ->join('customers', 'customers.id', '=', 'sales_orders.customer_id')
            ->selectRaw('customers.id as customer_id, customers.name as customer_name, sum(sales_orders.total_amount) as total_revenue')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_revenue')
            ->limit(self::TOP_N)
            ->get();

        return $query->map(fn ($row) => [
            'customer' => ['id' => $row->customer_id, 'name' => $row->customer_name],
            'total_revenue' => round((float) $row->total_revenue, 2),
        ])->all();
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TModel>  $query
     * @return \Illuminate\Database\Eloquent\Builder<TModel>
     */
    private function withDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->whereDate('order_date', '>=', Carbon::parse($from));
        }

        if ($to) {
            $query->whereDate('order_date', '<=', Carbon::parse($to));
        }

        return $query;
    }

    /**
     * @param  array<int, string>  $statuses
     * @param  \Illuminate\Support\Collection<string, int>  $counts
     * @return array<string, int>
     */
    private function fillStatuses(array $statuses, $counts): array
    {
        $result = [];

        foreach ($statuses as $status) {
            $result[$status] = (int) ($counts[$status] ?? 0);
        }

        return $result;
    }
}
