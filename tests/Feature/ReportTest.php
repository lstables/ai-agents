<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\SalesOrder;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_report_summary_returns_zeros_and_empty_arrays_with_no_data(): void
    {
        $response = $this->getJson('/api/reports/summary');

        $response->assertStatus(200);
        $response->assertJsonPath('purchasing.total_orders', 0);
        $response->assertJsonPath('purchasing.total_spend', 0);
        $response->assertJsonPath('sales.total_orders', 0);
        $response->assertJsonPath('sales.total_revenue', 0);
        $response->assertJsonPath('inventory.below_reorder_level_count', 0);
        $response->assertJsonCount(0, 'inventory.items');
        $response->assertJsonCount(0, 'top_suppliers');
        $response->assertJsonCount(0, 'top_customers');

        foreach (Purchase::statuses() as $status) {
            $response->assertJsonPath("purchasing.by_status.{$status}", 0);
        }

        foreach (SalesOrder::statuses() as $status) {
            $response->assertJsonPath("sales.by_status.{$status}", 0);
        }
    }

    public function test_purchasing_totals_exclude_cancelled_orders_but_still_count_them_by_status(): void
    {
        Purchase::factory()->status(Purchase::STATUS_APPROVED)->create(['total_amount' => 100]);
        Purchase::factory()->status(Purchase::STATUS_APPROVED)->create(['total_amount' => 50]);
        Purchase::factory()->status(Purchase::STATUS_CANCELLED)->create(['total_amount' => 999]);

        $response = $this->getJson('/api/reports/summary');

        $response->assertStatus(200);
        $response->assertJsonPath('purchasing.total_orders', 2);
        $this->assertEquals(150.0, $response->json('purchasing.total_spend'));
        $response->assertJsonPath('purchasing.by_status.approved', 2);
        $response->assertJsonPath('purchasing.by_status.cancelled', 1);
    }

    public function test_sales_totals_exclude_cancelled_orders_but_still_count_them_by_status(): void
    {
        SalesOrder::factory()->status(SalesOrder::STATUS_CONFIRMED)->create(['total_amount' => 200]);
        SalesOrder::factory()->status(SalesOrder::STATUS_CANCELLED)->create(['total_amount' => 500]);

        $response = $this->getJson('/api/reports/summary');

        $response->assertStatus(200);
        $response->assertJsonPath('sales.total_orders', 1);
        $this->assertEquals(200.0, $response->json('sales.total_revenue'));
        $response->assertJsonPath('sales.by_status.confirmed', 1);
        $response->assertJsonPath('sales.by_status.cancelled', 1);
    }

    public function test_the_inventory_summary_matches_the_below_reorder_level_scope(): void
    {
        $low = InventoryItem::factory()->belowReorderLevel()->create();
        InventoryItem::factory()->create(['quantity_on_hand' => 100, 'reorder_level' => 10]);
        InventoryItem::factory()->create(['quantity_on_hand' => 0, 'reorder_level' => null]);

        $response = $this->getJson('/api/reports/summary');

        $response->assertStatus(200);
        $response->assertJsonPath('inventory.below_reorder_level_count', 1);
        $response->assertJsonCount(1, 'inventory.items');
        $response->assertJsonPath('inventory.items.0.id', $low->id);
    }

    public function test_top_suppliers_are_ordered_by_spend_and_exclude_cancelled_purchases(): void
    {
        $topSupplier = Supplier::factory()->create(['name' => 'Big Spend Co']);
        $smallSupplier = Supplier::factory()->create(['name' => 'Small Spend Co']);
        $cancelledOnlySupplier = Supplier::factory()->create(['name' => 'Cancelled Only Co']);

        Purchase::factory()->status(Purchase::STATUS_APPROVED)->create(['supplier_id' => $topSupplier->id, 'total_amount' => 300]);
        Purchase::factory()->status(Purchase::STATUS_APPROVED)->create(['supplier_id' => $topSupplier->id, 'total_amount' => 200]);
        Purchase::factory()->status(Purchase::STATUS_APPROVED)->create(['supplier_id' => $smallSupplier->id, 'total_amount' => 50]);
        Purchase::factory()->status(Purchase::STATUS_CANCELLED)->create(['supplier_id' => $cancelledOnlySupplier->id, 'total_amount' => 9999]);

        $response = $this->getJson('/api/reports/summary');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'top_suppliers');
        $response->assertJsonPath('top_suppliers.0.supplier.id', $topSupplier->id);
        $this->assertEquals(500.0, $response->json('top_suppliers.0.total_spend'));
        $response->assertJsonPath('top_suppliers.1.supplier.id', $smallSupplier->id);

        $supplierIds = collect($response->json('top_suppliers'))->pluck('supplier.id');
        $this->assertFalse($supplierIds->contains($cancelledOnlySupplier->id));
    }

    public function test_top_customers_are_capped_at_five(): void
    {
        foreach (range(1, 7) as $i) {
            $customer = Customer::factory()->create();
            SalesOrder::factory()->status(SalesOrder::STATUS_FULFILLED)->create([
                'customer_id' => $customer->id,
                'total_amount' => $i * 10,
            ]);
        }

        $response = $this->getJson('/api/reports/summary');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'top_customers');
        // Highest total_amount (70) should be first.
        $this->assertEquals(70.0, $response->json('top_customers.0.total_revenue'));
    }

    public function test_the_date_range_filter_excludes_orders_outside_the_range_and_includes_boundary_dates(): void
    {
        Purchase::factory()->status(Purchase::STATUS_APPROVED)->create(['order_date' => '2026-01-01', 'total_amount' => 10]);
        Purchase::factory()->status(Purchase::STATUS_APPROVED)->create(['order_date' => '2026-01-15', 'total_amount' => 20]);
        Purchase::factory()->status(Purchase::STATUS_APPROVED)->create(['order_date' => '2026-01-31', 'total_amount' => 40]);
        Purchase::factory()->status(Purchase::STATUS_APPROVED)->create(['order_date' => '2026-02-01', 'total_amount' => 80]);

        $response = $this->getJson('/api/reports/summary?from=2026-01-01&to=2026-01-31');

        $response->assertStatus(200);
        $response->assertJsonPath('purchasing.total_orders', 3);
        $this->assertEquals(70.0, $response->json('purchasing.total_spend'));
    }

    public function test_the_date_range_filter_rejects_a_to_date_before_the_from_date(): void
    {
        $response = $this->getJson('/api/reports/summary?from=2026-02-01&to=2026-01-01');

        $response->assertStatus(422);
    }
}
