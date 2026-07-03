<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_request_with_no_session_is_resolved_as_the_demo_user_and_can_list_suppliers(): void
    {
        // This demo app has no login flow: ResolveDemoUser resolves every
        // request as a single demo user, so there is no unauthenticated
        // case to reject here.
        Supplier::factory()->count(3)->create();

        $response = $this->getJson('/api/suppliers');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_an_authenticated_user_can_list_suppliers(): void
    {
        $user = User::factory()->create();
        Supplier::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson('/api/suppliers');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_the_supplier_list_paginates_results(): void
    {
        $user = User::factory()->create();
        Supplier::factory()->count(25)->create();

        $response = $this->actingAs($user)->getJson('/api/suppliers?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('meta.total', 25);
        $response->assertJsonPath('meta.last_page', 3);
    }

    public function test_the_supplier_list_can_be_searched_by_name_or_email(): void
    {
        $user = User::factory()->create();
        $match = Supplier::factory()->create(['name' => 'Acme Bolts Ltd', 'email' => 'orders@acme.test']);
        Supplier::factory()->create(['name' => 'Other Co', 'email' => 'hello@other.test']);

        $byName = $this->actingAs($user)->getJson('/api/suppliers?search=Acme');
        $byName->assertStatus(200);
        $byName->assertJsonCount(1, 'data');
        $byName->assertJsonPath('data.0.id', $match->id);

        $byEmail = $this->actingAs($user)->getJson('/api/suppliers?search=orders@acme');
        $byEmail->assertStatus(200);
        $byEmail->assertJsonCount(1, 'data');
        $byEmail->assertJsonPath('data.0.id', $match->id);
    }

    public function test_a_request_with_no_session_is_resolved_as_the_demo_user_and_can_create_suppliers(): void
    {
        $response = $this->postJson('/api/suppliers', ['name' => 'New Supplier']);

        $response->assertStatus(201);
        $this->assertDatabaseHas('suppliers', ['name' => 'New Supplier']);
    }

    public function test_an_authenticated_user_can_create_a_supplier(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/suppliers', [
            'name' => 'Acme Bolts Ltd',
            'email' => 'orders@acme.test',
            'phone' => '555-0100',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Acme Bolts Ltd');
        $this->assertDatabaseHas('suppliers', ['name' => 'Acme Bolts Ltd', 'email' => 'orders@acme.test']);
    }

    public function test_creating_a_supplier_requires_a_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/suppliers', ['name' => '']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_creating_a_supplier_rejects_an_invalid_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/suppliers', [
            'name' => 'Acme Bolts Ltd',
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_an_authenticated_user_can_update_a_supplier(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->putJson("/api/suppliers/{$supplier->id}", [
            'name' => 'New Name',
            'email' => $supplier->email,
            'phone' => $supplier->phone,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'New Name');
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'New Name']);
    }

    public function test_updating_a_supplier_requires_a_name(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($user)->putJson("/api/suppliers/{$supplier->id}", ['name' => '']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_an_authenticated_user_can_delete_a_supplier_without_purchases(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($user)->deleteJson("/api/suppliers/{$supplier->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_deleting_a_supplier_with_existing_purchases_is_rejected(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        Purchase::factory()->create(['supplier_id' => $supplier->id]);

        $response = $this->actingAs($user)->deleteJson("/api/suppliers/{$supplier->id}");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['supplier']);
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_a_request_with_no_session_is_resolved_as_the_demo_user_and_can_delete_suppliers(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->deleteJson("/api/suppliers/{$supplier->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }
}
