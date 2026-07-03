<?php

namespace App\Actions\Reports;

use App\Models\Purchase;
use App\Models\SalesOrder;
use Illuminate\Support\Carbon;

class BuildActivityTrend
{
    /**
     * Build a daily activity trend for the last `$days` days (inclusive of
     * today). Every day in the range is present even if no orders were
     * placed that day. Cancelled orders are excluded from both the count
     * and the total, matching BuildReportSummary's money-total convention
     * — the trend is meant to show activity that actually stuck.
     *
     * @return array<string, mixed>
     */
    public function handle(int $days = 30): array
    {
        $to = Carbon::today();
        $from = $to->copy()->subDays($days - 1);

        return [
            'days' => $days,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'purchases' => $this->dailySeries(Purchase::class, Purchase::STATUS_CANCELLED, 'total_amount', $from, $to),
            'sales_orders' => $this->dailySeries(SalesOrder::class, SalesOrder::STATUS_CANCELLED, 'total_amount', $from, $to),
        ];
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @return list<array{date: string, count: int, total: float}>
     */
    private function dailySeries(string $model, string $cancelledStatus, string $totalColumn, Carbon $from, Carbon $to): array
    {
        $rows = $model::query()
            ->whereDate('order_date', '>=', $from)
            ->whereDate('order_date', '<=', $to)
            ->where('status', '!=', $cancelledStatus)
            ->selectRaw("date(order_date) as day, count(*) as count, coalesce(sum({$totalColumn}), 0) as total")
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $series = [];
        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $key = $date->toDateString();
            $row = $rows->get($key);

            $series[] = [
                'date' => $key,
                'count' => (int) ($row->count ?? 0),
                'total' => round((float) ($row->total ?? 0), 2),
            ];
        }

        return $series;
    }
}
