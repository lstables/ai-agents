<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_request_with_no_session_is_resolved_as_the_demo_user_and_can_list_inventory_items(): void
    {
        // This demo app has no login flow: ResolveDemoUser resolves every
        // request as a single demo user, so there is no unauthenticated
        // case to reject here.
        InventoryItem::factory()->count(2)->create();

        $response = $this->getJson('/api/inventory-items');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_an_inventory_item_can_be_created(): void
    {
        $response = $this->postJson('/api/inventory-items', [
            'sku' => 'SKU-001',
            'name' => 'Widget',
            'description' => 'A standard widget',
            'quantity_on_hand' => 50,
            'reorder_level' => 10,
            'unit' => 'each',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.sku', 'SKU-001');
        $response->assertJsonPath('data.is_below_reorder_level', false);
        $this->assertDatabaseHas('inventory_items', ['sku' => 'SKU-001', 'name' => 'Widget']);
    }

    public function test_creating_an_inventory_item_requires_a_name_and_sku(): void
    {
        $response = $this->postJson('/api/inventory-items', [
            'quantity_on_hand' => 10,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sku', 'name']);
        $this->assertDatabaseCount('inventory_items', 0);
    }

    public function test_creating_an_inventory_item_rejects_a_duplicate_sku(): void
    {
        InventoryItem::factory()->create(['sku' => 'SKU-DUPLICATE']);

        $response = $this->postJson('/api/inventory-items', [
            'sku' => 'SKU-DUPLICATE',
            'name' => 'Another Widget',
            'quantity_on_hand' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sku']);
        $this->assertDatabaseCount('inventory_items', 1);
    }

    public function test_creating_an_inventory_item_rejects_negative_quantity_on_hand(): void
    {
        $response = $this->postJson('/api/inventory-items', [
            'sku' => 'SKU-002',
            'name' => 'Widget',
            'quantity_on_hand' => -1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['quantity_on_hand']);
    }

    public function test_creating_an_inventory_item_rejects_a_negative_reorder_level(): void
    {
        $response = $this->postJson('/api/inventory-items', [
            'sku' => 'SKU-003',
            'name' => 'Widget',
            'quantity_on_hand' => 5,
            'reorder_level' => -5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reorder_level']);
    }

    public function test_creating_an_inventory_item_allows_zero_quantity_on_hand(): void
    {
        $response = $this->postJson('/api/inventory-items', [
            'sku' => 'SKU-004',
            'name' => 'Out of stock widget',
            'quantity_on_hand' => 0,
        ]);

        $response->assertStatus(201);
    }

    public function test_an_inventory_item_can_be_updated(): void
    {
        $item = InventoryItem::factory()->create(['name' => 'Old Name', 'quantity_on_hand' => 5]);

        $response = $this->putJson("/api/inventory-items/{$item->id}", [
            'sku' => $item->sku,
            'name' => 'New Name',
            'quantity_on_hand' => 20,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'New Name');
        $response->assertJsonPath('data.quantity_on_hand', 20);
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'name' => 'New Name', 'quantity_on_hand' => 20]);
    }

    public function test_updating_an_inventory_item_can_keep_its_own_sku(): void
    {
        $item = InventoryItem::factory()->create(['sku' => 'SKU-KEEP']);

        $response = $this->putJson("/api/inventory-items/{$item->id}", [
            'sku' => 'SKU-KEEP',
            'name' => 'Renamed',
            'quantity_on_hand' => 1,
        ]);

        $response->assertStatus(200);
    }

    public function test_updating_an_inventory_item_rejects_another_items_sku(): void
    {
        InventoryItem::factory()->create(['sku' => 'SKU-TAKEN']);
        $item = InventoryItem::factory()->create(['sku' => 'SKU-OWN']);

        $response = $this->putJson("/api/inventory-items/{$item->id}", [
            'sku' => 'SKU-TAKEN',
            'name' => 'Renamed',
            'quantity_on_hand' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sku']);
    }

    public function test_an_inventory_item_can_be_deleted(): void
    {
        $item = InventoryItem::factory()->create();

        $response = $this->deleteJson("/api/inventory-items/{$item->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('inventory_items', ['id' => $item->id]);
    }

    public function test_the_inventory_list_paginates_results(): void
    {
        InventoryItem::factory()->count(25)->create();

        $response = $this->getJson('/api/inventory-items?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('meta.total', 25);
        $response->assertJsonPath('meta.last_page', 3);
    }

    public function test_the_inventory_list_can_be_searched_by_name_or_sku(): void
    {
        $match = InventoryItem::factory()->create(['name' => 'Acme Widget', 'sku' => 'SKU-AAA']);
        InventoryItem::factory()->create(['name' => 'Other Item', 'sku' => 'SKU-BBB']);

        $byName = $this->getJson('/api/inventory-items?search=Acme');
        $byName->assertStatus(200);
        $byName->assertJsonCount(1, 'data');
        $byName->assertJsonPath('data.0.id', $match->id);

        $bySku = $this->getJson('/api/inventory-items?search=AAA');
        $bySku->assertStatus(200);
        $bySku->assertJsonCount(1, 'data');
        $bySku->assertJsonPath('data.0.id', $match->id);
    }

    public function test_the_inventory_list_can_be_filtered_by_below_reorder_level(): void
    {
        $low = InventoryItem::factory()->belowReorderLevel()->create();
        InventoryItem::factory()->create(['quantity_on_hand' => 100, 'reorder_level' => 10]);
        InventoryItem::factory()->create(['quantity_on_hand' => 50, 'reorder_level' => null]);

        $response = $this->getJson('/api/inventory-items?below_reorder_level=1');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $low->id);
    }

    public function test_items_without_a_reorder_level_are_never_considered_below_reorder_level(): void
    {
        $item = InventoryItem::factory()->create(['quantity_on_hand' => 0, 'reorder_level' => null]);

        $response = $this->getJson('/api/inventory-items?below_reorder_level=1');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
        $this->assertFalse($item->fresh()->isBelowReorderLevel());
    }

    public function test_the_below_reorder_level_filter_combines_correctly_with_search_and_pagination(): void
    {
        InventoryItem::factory()->count(3)->belowReorderLevel()->create(['name' => 'Acme Bolts']);
        InventoryItem::factory()->belowReorderLevel()->create(['name' => 'Other Low Stock']);
        InventoryItem::factory()->create(['name' => 'Acme Plenty', 'quantity_on_hand' => 100, 'reorder_level' => 10]);

        $response = $this->getJson('/api/inventory-items?below_reorder_level=1&search=Acme&per_page=10');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');

        foreach ($response->json('data') as $item) {
            $this->assertTrue($item['is_below_reorder_level']);
            $this->assertStringContainsString('Acme', $item['name']);
        }
    }

    public function test_an_inventory_item_can_be_created_with_a_preferred_supplier(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Acme Bolts Ltd']);

        $response = $this->postJson('/api/inventory-items', [
            'sku' => 'SKU-SUP-001',
            'name' => 'Widget',
            'quantity_on_hand' => 10,
            'supplier_id' => $supplier->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.supplier.id', $supplier->id);
        $response->assertJsonPath('data.supplier.name', 'Acme Bolts Ltd');
        $this->assertDatabaseHas('inventory_items', ['sku' => 'SKU-SUP-001', 'supplier_id' => $supplier->id]);
    }

    public function test_an_inventory_item_created_without_a_supplier_has_a_null_supplier(): void
    {
        $response = $this->postJson('/api/inventory-items', [
            'sku' => 'SKU-SUP-002',
            'name' => 'Widget',
            'quantity_on_hand' => 10,
        ]);

        $response->assertStatus(201);
        $this->assertNull($response->json('data.supplier'));
    }

    public function test_creating_an_inventory_item_rejects_an_invalid_supplier_id(): void
    {
        $response = $this->postJson('/api/inventory-items', [
            'sku' => 'SKU-SUP-003',
            'name' => 'Widget',
            'quantity_on_hand' => 10,
            'supplier_id' => 999999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['supplier_id']);
    }

    public function test_an_inventory_items_preferred_supplier_can_be_changed_on_update(): void
    {
        $originalSupplier = Supplier::factory()->create();
        $newSupplier = Supplier::factory()->create();
        $item = InventoryItem::factory()->create(['supplier_id' => $originalSupplier->id]);

        $response = $this->putJson("/api/inventory-items/{$item->id}", [
            'sku' => $item->sku,
            'name' => $item->name,
            'quantity_on_hand' => $item->quantity_on_hand,
            'supplier_id' => $newSupplier->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.supplier.id', $newSupplier->id);
    }

    public function test_an_inventory_items_supplier_becomes_null_when_the_supplier_is_deleted(): void
    {
        $supplier = Supplier::factory()->create();
        $item = InventoryItem::factory()->create(['supplier_id' => $supplier->id]);

        $supplier->delete();

        $this->assertNull($item->fresh()->supplier_id);
    }
}
