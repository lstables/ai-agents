<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_payment_can_be_recorded_against_a_purchase(): void
    {
        $purchase = Purchase::factory()->create(['total_amount' => 100]);

        $response = $this->postJson('/api/payments', [
            'payable_type' => 'purchase',
            'payable_id' => $purchase->id,
            'amount' => 40,
            'payment_date' => now()->toDateString(),
            'method' => 'bank_transfer',
            'reference' => 'TXN-001',
        ]);

        $response->assertStatus(201);
        $this->assertEquals(40.0, $response->json('data.amount'));
        $response->assertJsonPath('data.payable.type', 'purchase');
        $response->assertJsonPath('data.payable.id', $purchase->id);

        $this->assertDatabaseHas('payments', [
            'payable_type' => 'purchase',
            'payable_id' => $purchase->id,
            'amount' => 40,
        ]);

        $this->assertEquals(40.0, $purchase->fresh()->amountPaid());
        $this->assertEquals(60.0, $purchase->fresh()->balanceDue());
    }

    public function test_a_payment_can_be_recorded_against_a_sales_order(): void
    {
        $salesOrder = SalesOrder::factory()->create(['total_amount' => 200]);

        $response = $this->postJson('/api/payments', [
            'payable_type' => 'sales_order',
            'payable_id' => $salesOrder->id,
            'amount' => 75,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.payable.type', 'sales_order');

        $this->assertEquals(75.0, $salesOrder->fresh()->amountPaid());
        $this->assertEquals(125.0, $salesOrder->fresh()->balanceDue());
    }

    public function test_amount_paid_and_balance_due_accumulate_across_multiple_partial_payments(): void
    {
        $purchase = Purchase::factory()->create(['total_amount' => 100]);

        $this->postJson('/api/payments', [
            'payable_type' => 'purchase', 'payable_id' => $purchase->id,
            'amount' => 30, 'payment_date' => now()->toDateString(),
        ])->assertStatus(201);

        $this->postJson('/api/payments', [
            'payable_type' => 'purchase', 'payable_id' => $purchase->id,
            'amount' => 20, 'payment_date' => now()->toDateString(),
        ])->assertStatus(201);

        $this->assertEquals(50.0, $purchase->fresh()->amountPaid());
        $this->assertEquals(50.0, $purchase->fresh()->balanceDue());
    }

    public function test_a_purchase_with_no_payments_has_the_full_balance_due(): void
    {
        $purchase = Purchase::factory()->create(['total_amount' => 100]);

        $this->assertEquals(0.0, $purchase->amountPaid());
        $this->assertEquals(100.0, $purchase->balanceDue());
    }

    public function test_a_payment_exactly_equal_to_the_balance_due_is_accepted(): void
    {
        $purchase = Purchase::factory()->create(['total_amount' => 50]);

        $response = $this->postJson('/api/payments', [
            'payable_type' => 'purchase',
            'payable_id' => $purchase->id,
            'amount' => 50,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertStatus(201);
        $this->assertEquals(0.0, $purchase->fresh()->balanceDue());
    }

    public function test_a_payment_exceeding_the_balance_due_is_rejected_for_a_purchase(): void
    {
        $purchase = Purchase::factory()->create(['total_amount' => 50]);

        $response = $this->postJson('/api/payments', [
            'payable_type' => 'purchase',
            'payable_id' => $purchase->id,
            'amount' => 50.01,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_a_payment_exceeding_the_balance_due_is_rejected_for_a_sales_order(): void
    {
        $salesOrder = SalesOrder::factory()->create(['total_amount' => 50]);

        $response = $this->postJson('/api/payments', [
            'payable_type' => 'sales_order',
            'payable_id' => $salesOrder->id,
            'amount' => 100,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }

    public function test_a_second_payment_that_would_exceed_the_remaining_balance_is_rejected(): void
    {
        $purchase = Purchase::factory()->create(['total_amount' => 100]);

        $this->postJson('/api/payments', [
            'payable_type' => 'purchase', 'payable_id' => $purchase->id,
            'amount' => 80, 'payment_date' => now()->toDateString(),
        ])->assertStatus(201);

        $response = $this->postJson('/api/payments', [
            'payable_type' => 'purchase', 'payable_id' => $purchase->id,
            'amount' => 21, 'payment_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
        $this->assertEquals(80.0, $purchase->fresh()->amountPaid());
    }

    public function test_any_payment_on_a_fully_paid_order_is_rejected(): void
    {
        $purchase = Purchase::factory()->create(['total_amount' => 50]);

        $this->postJson('/api/payments', [
            'payable_type' => 'purchase', 'payable_id' => $purchase->id,
            'amount' => 50, 'payment_date' => now()->toDateString(),
        ])->assertStatus(201);

        $response = $this->postJson('/api/payments', [
            'payable_type' => 'purchase', 'payable_id' => $purchase->id,
            'amount' => 0.01, 'payment_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }

    public function test_recording_a_payment_rejects_a_negative_or_zero_amount(): void
    {
        $purchase = Purchase::factory()->create(['total_amount' => 100]);

        $negative = $this->postJson('/api/payments', [
            'payable_type' => 'purchase', 'payable_id' => $purchase->id,
            'amount' => -10, 'payment_date' => now()->toDateString(),
        ]);
        $negative->assertStatus(422);
        $negative->assertJsonValidationErrors(['amount']);

        $zero = $this->postJson('/api/payments', [
            'payable_type' => 'purchase', 'payable_id' => $purchase->id,
            'amount' => 0, 'payment_date' => now()->toDateString(),
        ]);
        $zero->assertStatus(422);
        $zero->assertJsonValidationErrors(['amount']);
    }

    public function test_the_payments_list_filter_and_search_combine_correctly(): void
    {
        $purchase = Purchase::factory()->create(['reference' => 'PO-COMBO', 'total_amount' => 100]);
        $otherPurchase = Purchase::factory()->create(['reference' => 'PO-OTHER', 'total_amount' => 100]);
        $salesOrder = SalesOrder::factory()->create(['reference' => 'SO-COMBO', 'total_amount' => 100]);

        $match = Payment::factory()->for($purchase, 'payable')->create(['reference' => 'TXN-A']);
        Payment::factory()->for($salesOrder, 'payable')->create(['reference' => 'TXN-B']); // right search term, wrong type
        Payment::factory()->for($otherPurchase, 'payable')->create(['reference' => 'TXN-C']); // right type, wrong search term

        $response = $this->getJson('/api/payments?payable_type=purchase&search=COMBO');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_recording_a_payment_rejects_an_invalid_payable_type(): void
    {
        $purchase = Purchase::factory()->create();

        $response = $this->postJson('/api/payments', [
            'payable_type' => 'App\\Models\\Purchase',
            'payable_id' => $purchase->id,
            'amount' => 10,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payable_type']);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_recording_a_payment_rejects_an_unknown_payable_id(): void
    {
        $response = $this->postJson('/api/payments', [
            'payable_type' => 'purchase',
            'payable_id' => 999999,
            'amount' => 10,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payable_id']);
    }

    public function test_recording_a_payment_requires_a_payable_type_and_amount(): void
    {
        $response = $this->postJson('/api/payments', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payable_type', 'payable_id', 'amount', 'payment_date']);
    }

    public function test_the_payments_list_paginates_results(): void
    {
        Payment::factory()->count(25)->create();

        $response = $this->getJson('/api/payments?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('meta.total', 25);
    }

    public function test_the_payments_list_can_be_filtered_by_payable_type(): void
    {
        $purchase = Purchase::factory()->create(['total_amount' => 100]);
        $salesOrder = SalesOrder::factory()->create(['total_amount' => 100]);

        Payment::factory()->count(2)->for($purchase, 'payable')->create();
        Payment::factory()->count(3)->for($salesOrder, 'payable')->create();

        $response = $this->getJson('/api/payments?payable_type=sales_order');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_the_payments_list_can_be_searched_by_payment_reference_or_order_reference(): void
    {
        $purchase = Purchase::factory()->create(['reference' => 'PO-FINDME', 'total_amount' => 100]);
        $match = Payment::factory()->for($purchase, 'payable')->create(['reference' => 'TXN-999']);
        Payment::factory()->create(['reference' => 'TXN-OTHER']);

        $byPaymentReference = $this->getJson('/api/payments?search=TXN-999');
        $byPaymentReference->assertStatus(200);
        $byPaymentReference->assertJsonCount(1, 'data');
        $byPaymentReference->assertJsonPath('data.0.id', $match->id);

        $byOrderReference = $this->getJson('/api/payments?search=PO-FINDME');
        $byOrderReference->assertStatus(200);
        $byOrderReference->assertJsonCount(1, 'data');
        $byOrderReference->assertJsonPath('data.0.id', $match->id);
    }
}
