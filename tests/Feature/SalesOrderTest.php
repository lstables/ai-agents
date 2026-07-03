<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_request_with_no_session_is_resolved_as_the_demo_user_and_can_list_sales_orders(): void
    {
        // This demo app has no login flow: ResolveDemoUser resolves every
        // request as a single demo user, so there is no unauthenticated
        // case to reject here.
        SalesOrder::factory()->count(2)->create();

        $response = $this->getJson('/api/sales-orders');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_a_sales_order_can_be_created_with_free_text_line_items(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/sales-orders', [
            'customer_id' => $customer->id,
            'order_date' => '2026-01-10',
            'expected_date' => '2026-01-20',
            'notes' => 'Rush order',
            'items' => [
                ['description' => 'Widget', 'quantity' => 2, 'unit_price' => 10],
                ['description' => 'Gadget', 'quantity' => 1.5, 'unit_price' => 4.1],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', SalesOrder::STATUS_DRAFT);
        $response->assertJsonPath('data.total_amount', 26.15);
        $response->assertJsonPath('data.customer.id', $customer->id);
        $response->assertJsonCount(2, 'data.items');
        $response->assertJsonPath('data.items.0.inventory_item', null);

        $this->assertDatabaseCount('sales_orders', 1);
        $this->assertDatabaseCount('sales_order_items', 2);

        $salesOrder = SalesOrder::first();
        $this->assertNotEmpty($salesOrder->reference);
        $this->assertEquals(26.15, (float) $salesOrder->total_amount);
    }

    public function test_a_sales_order_line_item_can_reference_an_inventory_item(): void
    {
        $customer = Customer::factory()->create();
        $inventoryItem = InventoryItem::factory()->create(['sku' => 'SKU-LINK', 'name' => 'Linked Widget']);

        $response = $this->postJson('/api/sales-orders', [
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'description' => 'Linked Widget',
                    'quantity' => 3,
                    'unit_price' => 9.5,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.items.0.inventory_item.id', $inventoryItem->id);
        $response->assertJsonPath('data.items.0.inventory_item.sku', 'SKU-LINK');

        $this->assertDatabaseHas('sales_order_items', [
            'inventory_item_id' => $inventoryItem->id,
            'description' => 'Linked Widget',
        ]);
    }

    public function test_creating_a_sales_order_rejects_an_invalid_inventory_item_id(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/sales-orders', [
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'items' => [
                ['inventory_item_id' => 999999, 'description' => 'Ghost item', 'quantity' => 1, 'unit_price' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.inventory_item_id']);
    }

    public function test_creating_a_sales_order_requires_a_valid_customer_and_at_least_one_item(): void
    {
        $response = $this->postJson('/api/sales-orders', [
            'customer_id' => 999999,
            'order_date' => now()->toDateString(),
            'items' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_id', 'items']);
        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_creating_a_sales_order_rejects_negative_quantity_or_price(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/sales-orders', [
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'items' => [
                ['description' => 'Widget', 'quantity' => -1, 'unit_price' => -5],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.quantity', 'items.0.unit_price']);
    }

    public function test_creating_a_sales_order_rejects_an_expected_date_before_the_order_date(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/sales-orders', [
            'customer_id' => $customer->id,
            'order_date' => '2026-02-10',
            'expected_date' => '2026-02-01',
            'items' => [
                ['description' => 'Widget', 'quantity' => 1, 'unit_price' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['expected_date']);
    }

    public function test_the_sales_orders_list_paginates_results(): void
    {
        SalesOrder::factory()->count(25)->create();

        $response = $this->getJson('/api/sales-orders?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('meta.total', 25);
        $response->assertJsonPath('meta.last_page', 3);
    }

    public function test_the_sales_orders_list_can_be_filtered_by_status(): void
    {
        SalesOrder::factory()->count(2)->status(SalesOrder::STATUS_CONFIRMED)->create();
        SalesOrder::factory()->count(3)->status(SalesOrder::STATUS_CANCELLED)->create();

        $response = $this->getJson('/api/sales-orders?status=confirmed');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_the_sales_orders_list_can_be_filtered_by_customer(): void
    {
        $customerA = Customer::factory()->create();
        $customerB = Customer::factory()->create();

        SalesOrder::factory()->count(2)->create(['customer_id' => $customerA->id]);
        SalesOrder::factory()->count(4)->create(['customer_id' => $customerB->id]);

        $response = $this->getJson("/api/sales-orders?customer_id={$customerA->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_the_sales_orders_list_can_be_searched_by_reference_or_customer_name(): void
    {
        $customer = Customer::factory()->create(['name' => 'Acme Retail']);
        $match = SalesOrder::factory()->create(['customer_id' => $customer->id]);
        SalesOrder::factory()->create(['reference' => 'SO-OTHER-002']);

        $byReference = $this->getJson("/api/sales-orders?search={$match->reference}");
        $byReference->assertStatus(200);
        $byReference->assertJsonCount(1, 'data');
        $byReference->assertJsonPath('data.0.id', $match->id);

        $byCustomer = $this->getJson('/api/sales-orders?search=Acme');
        $byCustomer->assertStatus(200);
        $byCustomer->assertJsonCount(1, 'data');
        $byCustomer->assertJsonPath('data.0.id', $match->id);
    }

    public function test_pagination_is_correct_when_combined_with_a_status_filter(): void
    {
        SalesOrder::factory()->count(12)->status(SalesOrder::STATUS_CONFIRMED)->create();
        SalesOrder::factory()->count(5)->status(SalesOrder::STATUS_CANCELLED)->create();

        $firstPage = $this->getJson('/api/sales-orders?status=confirmed&per_page=10&page=1');
        $firstPage->assertStatus(200);
        $firstPage->assertJsonCount(10, 'data');
        $firstPage->assertJsonPath('meta.total', 12);

        $secondPage = $this->getJson('/api/sales-orders?status=confirmed&per_page=10&page=2');
        $secondPage->assertStatus(200);
        $secondPage->assertJsonCount(2, 'data');

        $firstPageIds = collect($firstPage->json('data'))->pluck('id');
        $secondPageIds = collect($secondPage->json('data'))->pluck('id');
        $this->assertEmpty($firstPageIds->intersect($secondPageIds));
    }
}
