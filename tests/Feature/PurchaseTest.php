<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_request_with_no_session_is_resolved_as_the_demo_user_and_can_list_purchases(): void
    {
        // This demo app has no login flow: ResolveDemoUser resolves every
        // request as a single demo user, so there is no unauthenticated
        // case to reject here.
        Purchase::factory()->count(2)->create();

        $response = $this->getJson('/api/purchases');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_a_request_with_no_session_is_resolved_as_the_demo_user_and_can_create_purchases(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [
                ['description' => 'Widget', 'quantity' => 2, 'unit_price' => 10],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('purchases', 1);

        $purchase = Purchase::first();
        $this->assertSame('demo@example.com', $purchase->creator->email);
    }

    public function test_an_authenticated_user_can_create_a_purchase_with_line_items(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'order_date' => '2026-01-10',
            'expected_date' => '2026-01-20',
            'notes' => 'Urgent restock',
            'items' => [
                ['description' => 'Widget', 'quantity' => 2, 'unit_price' => 10],
                ['description' => 'Gadget', 'quantity' => 1.5, 'unit_price' => 4.1],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', Purchase::STATUS_DRAFT);
        $response->assertJsonPath('data.total_amount', 26.15);
        $response->assertJsonPath('data.supplier.id', $supplier->id);
        $response->assertJsonCount(2, 'data.items');

        $this->assertDatabaseCount('purchases', 1);
        $this->assertDatabaseCount('purchase_items', 2);

        $purchase = Purchase::first();
        $this->assertSame($user->id, $purchase->created_by);
        $this->assertNotEmpty($purchase->reference);
        $this->assertEquals(26.15, (float) $purchase->total_amount);
    }

    public function test_creating_a_purchase_requires_a_valid_supplier_and_at_least_one_item(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/purchases', [
            'supplier_id' => 999,
            'order_date' => now()->toDateString(),
            'items' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['supplier_id', 'items']);
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_creating_a_purchase_rejects_negative_quantity_or_price(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [
                ['description' => 'Widget', 'quantity' => -1, 'unit_price' => -5],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.quantity', 'items.0.unit_price']);
    }

    public function test_creating_a_purchase_rejects_zero_quantity(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [
                ['description' => 'Widget', 'quantity' => 0, 'unit_price' => 5],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.quantity']);
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_creating_a_purchase_allows_a_zero_unit_price_line_item(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [
                ['description' => 'Free sample', 'quantity' => 1, 'unit_price' => 0],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertEquals(0.0, $response->json('data.total_amount'));
    }

    public function test_creating_a_purchase_with_many_line_items_sums_the_total_correctly(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();

        $items = [];
        $expectedTotal = 0;

        for ($i = 1; $i <= 50; $i++) {
            $quantity = 1;
            $unitPrice = 3.33;
            $items[] = ['description' => "Item {$i}", 'quantity' => $quantity, 'unit_price' => $unitPrice];
            $expectedTotal += round($quantity * $unitPrice, 2);
        }

        $response = $this->actingAs($user)->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => $items,
        ]);

        $response->assertStatus(201);
        $response->assertJsonCount(50, 'data.items');
        $response->assertJsonPath('data.total_amount', round($expectedTotal, 2));
        $this->assertDatabaseCount('purchase_items', 50);
    }

    public function test_purchase_totals_do_not_drift_across_several_fractional_line_items(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [
                ['description' => 'A', 'quantity' => 3, 'unit_price' => 0.1],
                ['description' => 'B', 'quantity' => 1, 'unit_price' => 0.2],
                ['description' => 'C', 'quantity' => 7, 'unit_price' => 0.03],
            ],
        ]);

        $response->assertStatus(201);
        // 3*0.1 + 1*0.2 + 7*0.03 = 0.3 + 0.2 + 0.21 = 0.71, susceptible to float drift if summed unrounded.
        $response->assertJsonPath('data.total_amount', 0.71);

        $purchase = Purchase::first();
        $this->assertEquals(0.71, (float) $purchase->total_amount);
    }

    public function test_creating_a_purchase_rejects_an_expected_date_before_the_order_date(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'order_date' => '2026-02-10',
            'expected_date' => '2026-02-01',
            'items' => [
                ['description' => 'Widget', 'quantity' => 1, 'unit_price' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['expected_date']);
    }

    public function test_an_authenticated_user_can_list_purchases(): void
    {
        $user = User::factory()->create();
        Purchase::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson('/api/purchases');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_the_purchases_list_paginates_results(): void
    {
        $user = User::factory()->create();
        Purchase::factory()->count(25)->create();

        $response = $this->actingAs($user)->getJson('/api/purchases?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('meta.total', 25);
        $response->assertJsonPath('meta.last_page', 3);

        $secondPage = $this->actingAs($user)->getJson('/api/purchases?per_page=10&page=2');
        $secondPage->assertStatus(200);
        $secondPage->assertJsonCount(10, 'data');
    }

    public function test_the_purchases_list_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        Purchase::factory()->count(2)->status(Purchase::STATUS_APPROVED)->create();
        Purchase::factory()->count(3)->status(Purchase::STATUS_CANCELLED)->create();

        $response = $this->actingAs($user)->getJson('/api/purchases?status=approved');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_the_purchases_list_can_be_filtered_by_supplier(): void
    {
        $user = User::factory()->create();
        $supplierA = Supplier::factory()->create();
        $supplierB = Supplier::factory()->create();

        Purchase::factory()->count(2)->create(['supplier_id' => $supplierA->id]);
        Purchase::factory()->count(4)->create(['supplier_id' => $supplierB->id]);

        $response = $this->actingAs($user)->getJson("/api/purchases?supplier_id={$supplierA->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_the_purchases_list_can_be_searched_by_reference(): void
    {
        $user = User::factory()->create();
        $match = Purchase::factory()->create(['reference' => 'PO-FINDME-001']);
        Purchase::factory()->create(['reference' => 'PO-OTHER-002']);

        $response = $this->actingAs($user)->getJson('/api/purchases?search=FINDME');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_the_purchases_list_can_be_searched_by_supplier_name(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['name' => 'Acme Bolts Ltd']);
        $match = Purchase::factory()->create(['supplier_id' => $supplier->id]);
        Purchase::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/purchases?search=Acme');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_cancelled_purchases_are_not_double_counted_when_filtering_and_searching_together(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['name' => 'Acme Bolts Ltd']);

        $match = Purchase::factory()->status(Purchase::STATUS_PENDING)->create(['supplier_id' => $supplier->id]);
        Purchase::factory()->status(Purchase::STATUS_CANCELLED)->create(['supplier_id' => $supplier->id]);
        Purchase::factory()->status(Purchase::STATUS_PENDING)->create();

        $response = $this->actingAs($user)->getJson('/api/purchases?status=pending&search=Acme');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_pagination_is_correct_when_combined_with_a_status_filter(): void
    {
        $user = User::factory()->create();
        Purchase::factory()->count(12)->status(Purchase::STATUS_APPROVED)->create();
        Purchase::factory()->count(5)->status(Purchase::STATUS_CANCELLED)->create();

        $firstPage = $this->actingAs($user)->getJson('/api/purchases?status=approved&per_page=10&page=1');
        $firstPage->assertStatus(200);
        $firstPage->assertJsonCount(10, 'data');
        $firstPage->assertJsonPath('meta.total', 12);
        $firstPage->assertJsonPath('meta.last_page', 2);

        foreach ($firstPage->json('data') as $purchase) {
            $this->assertSame(Purchase::STATUS_APPROVED, $purchase['status']);
        }

        $secondPage = $this->actingAs($user)->getJson('/api/purchases?status=approved&per_page=10&page=2');
        $secondPage->assertStatus(200);
        $secondPage->assertJsonCount(2, 'data');

        foreach ($secondPage->json('data') as $purchase) {
            $this->assertSame(Purchase::STATUS_APPROVED, $purchase['status']);
        }

        $firstPageIds = collect($firstPage->json('data'))->pluck('id');
        $secondPageIds = collect($secondPage->json('data'))->pluck('id');
        $this->assertEmpty($firstPageIds->intersect($secondPageIds), 'Pages should not overlap or double-count results.');
    }
}
