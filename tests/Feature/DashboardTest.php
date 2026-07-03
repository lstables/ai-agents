<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_the_overview_includes_both_the_report_summary_and_the_trend(): void
    {
        Purchase::factory()->status(Purchase::STATUS_APPROVED)->create(['total_amount' => 100]);

        $response = $this->getJson('/api/dashboard/overview');

        $response->assertStatus(200);
        $response->assertJsonPath('summary.purchasing.total_orders', 1);
        $this->assertEquals(100.0, $response->json('summary.purchasing.total_spend'));
        $response->assertJsonPath('trend.days', 30);
        $response->assertJsonCount(30, 'trend.purchases');
        $response->assertJsonCount(30, 'trend.sales_orders');
    }

    public function test_the_trend_is_zero_filled_with_no_data(): void
    {
        $response = $this->getJson('/api/dashboard/overview');

        $response->assertStatus(200);

        foreach ($response->json('trend.purchases') as $day) {
            $this->assertSame(0, $day['count']);
            $this->assertEquals(0.0, $day['total']);
        }

        foreach ($response->json('trend.sales_orders') as $day) {
            $this->assertSame(0, $day['count']);
            $this->assertEquals(0.0, $day['total']);
        }
    }

    public function test_the_trend_aggregates_orders_by_day_and_excludes_orders_outside_the_window(): void
    {
        Carbon::setTestNow('2026-01-31');

        Purchase::factory()->status(Purchase::STATUS_APPROVED)->create(['order_date' => '2026-01-31', 'total_amount' => 10]);
        Purchase::factory()->status(Purchase::STATUS_APPROVED)->create(['order_date' => '2026-01-31', 'total_amount' => 20]);
        Purchase::factory()->status(Purchase::STATUS_APPROVED)->create(['order_date' => '2026-01-15', 'total_amount' => 5]);
        // Outside the 30-day window (2026-01-02..2026-01-31).
        Purchase::factory()->status(Purchase::STATUS_APPROVED)->create(['order_date' => '2025-12-01', 'total_amount' => 999]);

        $response = $this->getJson('/api/dashboard/overview');

        $response->assertStatus(200);
        $response->assertJsonPath('trend.from', '2026-01-02');
        $response->assertJsonPath('trend.to', '2026-01-31');

        $series = collect($response->json('trend.purchases'))->keyBy('date');

        $this->assertSame(2, $series['2026-01-31']['count']);
        $this->assertEquals(30.0, $series['2026-01-31']['total']);
        $this->assertSame(1, $series['2026-01-15']['count']);
        $this->assertEquals(5.0, $series['2026-01-15']['total']);
        $this->assertFalse($series->has('2025-12-01'));
    }

    public function test_the_trend_excludes_cancelled_orders_from_count_and_total(): void
    {
        Carbon::setTestNow('2026-01-31');

        Purchase::factory()->status(Purchase::STATUS_APPROVED)->create(['order_date' => '2026-01-31', 'total_amount' => 10]);
        Purchase::factory()->status(Purchase::STATUS_CANCELLED)->create(['order_date' => '2026-01-31', 'total_amount' => 999]);

        SalesOrder::factory()->status(SalesOrder::STATUS_CONFIRMED)->create(['order_date' => '2026-01-31', 'total_amount' => 40]);
        SalesOrder::factory()->status(SalesOrder::STATUS_CANCELLED)->create(['order_date' => '2026-01-31', 'total_amount' => 500]);

        $response = $this->getJson('/api/dashboard/overview');

        $response->assertStatus(200);

        $purchases = collect($response->json('trend.purchases'))->keyBy('date');
        $salesOrders = collect($response->json('trend.sales_orders'))->keyBy('date');

        $this->assertSame(1, $purchases['2026-01-31']['count']);
        $this->assertEquals(10.0, $purchases['2026-01-31']['total']);
        $this->assertSame(1, $salesOrders['2026-01-31']['count']);
        $this->assertEquals(40.0, $salesOrders['2026-01-31']['total']);
    }
}
