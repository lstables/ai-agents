<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers GET /api/suppliers, the supplier lookup the purchase create form
 * depends on. This endpoint shipped with the Purchasing module but had no
 * direct test coverage of its own (only exercised indirectly via factories
 * in PurchaseTest).
 */
class SupplierApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_list_suppliers(): void
    {
        $response = $this->getJson('/api/suppliers');

        $response->assertStatus(401);
    }

    public function test_an_authenticated_user_can_list_suppliers(): void
    {
        $user = User::factory()->create();
        Supplier::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson('/api/suppliers');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure(['data' => [['id', 'name', 'email', 'phone']]]);
    }

    public function test_suppliers_are_returned_in_alphabetical_order(): void
    {
        $user = User::factory()->create();
        Supplier::factory()->create(['name' => 'Zeta Supplies']);
        Supplier::factory()->create(['name' => 'Acme Bolts Ltd']);
        Supplier::factory()->create(['name' => 'Midway Traders']);

        $response = $this->actingAs($user)->getJson('/api/suppliers');

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('name');
        $this->assertSame(['Acme Bolts Ltd', 'Midway Traders', 'Zeta Supplies'], $names->all());
    }
}
