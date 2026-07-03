<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_request_with_no_session_is_resolved_as_the_demo_user_and_can_list_customers(): void
    {
        // This demo app has no login flow: ResolveDemoUser resolves every
        // request as a single demo user, so there is no unauthenticated
        // case to reject here.
        Customer::factory()->count(3)->create();

        $response = $this->getJson('/api/customers');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_the_customer_list_paginates_results(): void
    {
        Customer::factory()->count(25)->create();

        $response = $this->getJson('/api/customers?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('meta.total', 25);
        $response->assertJsonPath('meta.last_page', 3);
    }

    public function test_the_customer_list_can_be_searched_by_name_or_email(): void
    {
        $match = Customer::factory()->create(['name' => 'Jane Roe', 'email' => 'jane@example.test']);
        Customer::factory()->create(['name' => 'John Smith', 'email' => 'john@other.test']);

        $byName = $this->getJson('/api/customers?search=Jane');
        $byName->assertStatus(200);
        $byName->assertJsonCount(1, 'data');
        $byName->assertJsonPath('data.0.id', $match->id);

        $byEmail = $this->getJson('/api/customers?search=jane@example');
        $byEmail->assertStatus(200);
        $byEmail->assertJsonCount(1, 'data');
        $byEmail->assertJsonPath('data.0.id', $match->id);
    }

    public function test_the_customer_list_search_combines_correctly_with_pagination(): void
    {
        Customer::factory()->count(15)->create(['name' => 'Acme Customer']);
        Customer::factory()->create(['name' => 'Someone Else']);

        $response = $this->getJson('/api/customers?search=Acme&per_page=10');

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('meta.total', 15);
        $response->assertJsonPath('meta.last_page', 2);

        foreach ($response->json('data') as $customer) {
            $this->assertStringContainsString('Acme', $customer['name']);
        }
    }

    public function test_a_customer_can_be_created(): void
    {
        $response = $this->postJson('/api/customers', [
            'name' => 'Jane Roe',
            'email' => 'jane@example.test',
            'phone' => '555-0100',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Jane Roe');
        $this->assertDatabaseHas('customers', ['name' => 'Jane Roe', 'email' => 'jane@example.test']);
    }

    public function test_a_customer_can_be_created_with_only_a_name(): void
    {
        $response = $this->postJson('/api/customers', ['name' => 'Jane Roe']);

        $response->assertStatus(201);
        $this->assertDatabaseHas('customers', ['name' => 'Jane Roe', 'email' => null, 'phone' => null]);
    }

    public function test_creating_a_customer_requires_a_name(): void
    {
        $response = $this->postJson('/api/customers', ['name' => '']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_creating_a_customer_rejects_an_invalid_email(): void
    {
        $response = $this->postJson('/api/customers', [
            'name' => 'Jane Roe',
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_a_customer_can_be_updated(): void
    {
        $customer = Customer::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/customers/{$customer->id}", [
            'name' => 'New Name',
            'email' => $customer->email,
            'phone' => $customer->phone,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'New Name');
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'New Name']);
    }

    public function test_updating_a_customer_requires_a_name(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->putJson("/api/customers/{$customer->id}", ['name' => '']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_updating_a_customer_rejects_an_invalid_email(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->putJson("/api/customers/{$customer->id}", [
            'name' => $customer->name,
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_creating_a_customer_rejects_a_name_over_255_characters(): void
    {
        $response = $this->postJson('/api/customers', ['name' => str_repeat('a', 256)]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_updating_a_customer_with_only_a_name_does_not_clear_existing_email_or_phone(): void
    {
        $customer = Customer::factory()->create(['email' => 'keep@example.test', 'phone' => '555-9999']);

        $response = $this->putJson("/api/customers/{$customer->id}", ['name' => 'Renamed']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Renamed',
            'email' => 'keep@example.test',
            'phone' => '555-9999',
        ]);
    }

    public function test_a_customer_can_be_deleted(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->deleteJson("/api/customers/{$customer->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_deleting_a_customer_with_existing_sales_orders_is_rejected(): void
    {
        $customer = Customer::factory()->create();
        SalesOrder::factory()->create(['customer_id' => $customer->id]);

        $response = $this->deleteJson("/api/customers/{$customer->id}");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer']);
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }
}
