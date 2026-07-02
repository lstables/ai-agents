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

    public function test_guests_cannot_list_purchases(): void
    {
        $response = $this->getJson('/api/purchases');

        $response->assertStatus(401);
    }

    public function test_guests_cannot_create_purchases(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [
                ['description' => 'Widget', 'quantity' => 2, 'unit_price' => 10],
            ],
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseCount('purchases', 0);
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
}
