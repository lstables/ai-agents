<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_sales_order_can_move_through_each_legal_transition(): void
    {
        $salesOrder = SalesOrder::factory()->status(SalesOrder::STATUS_DRAFT)->create();

        foreach ([SalesOrder::STATUS_PENDING, SalesOrder::STATUS_CONFIRMED, SalesOrder::STATUS_FULFILLED] as $nextStatus) {
            $response = $this->patchJson("/api/sales-orders/{$salesOrder->id}/status", ['status' => $nextStatus]);

            $response->assertStatus(200);
            $response->assertJsonPath('data.status', $nextStatus);
            $this->assertSame($nextStatus, $salesOrder->fresh()->status);
        }
    }

    public function test_a_sales_order_can_be_cancelled_from_any_non_terminal_status(): void
    {
        foreach ([SalesOrder::STATUS_DRAFT, SalesOrder::STATUS_PENDING, SalesOrder::STATUS_CONFIRMED] as $status) {
            $salesOrder = SalesOrder::factory()->status($status)->create();

            $response = $this->patchJson("/api/sales-orders/{$salesOrder->id}/status", ['status' => SalesOrder::STATUS_CANCELLED]);

            $response->assertStatus(200);
            $response->assertJsonPath('data.status', SalesOrder::STATUS_CANCELLED);
        }
    }

    public function test_a_skipped_transition_is_rejected(): void
    {
        $salesOrder = SalesOrder::factory()->status(SalesOrder::STATUS_DRAFT)->create();

        $response = $this->patchJson("/api/sales-orders/{$salesOrder->id}/status", ['status' => SalesOrder::STATUS_FULFILLED]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
        $this->assertSame(SalesOrder::STATUS_DRAFT, $salesOrder->fresh()->status);
    }

    public function test_a_terminal_status_cannot_transition_further(): void
    {
        $fulfilled = SalesOrder::factory()->status(SalesOrder::STATUS_FULFILLED)->create();
        $cancelled = SalesOrder::factory()->status(SalesOrder::STATUS_CANCELLED)->create();

        $this->patchJson("/api/sales-orders/{$fulfilled->id}/status", ['status' => SalesOrder::STATUS_CANCELLED])
            ->assertStatus(422);
        $this->patchJson("/api/sales-orders/{$cancelled->id}/status", ['status' => SalesOrder::STATUS_DRAFT])
            ->assertStatus(422);
    }

    public function test_an_invalid_status_value_is_rejected(): void
    {
        $salesOrder = SalesOrder::factory()->status(SalesOrder::STATUS_DRAFT)->create();

        $response = $this->patchJson("/api/sales-orders/{$salesOrder->id}/status", ['status' => 'not-a-real-status']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_cancelling_a_sales_order_with_no_payments_succeeds(): void
    {
        $salesOrder = SalesOrder::factory()->status(SalesOrder::STATUS_CONFIRMED)->create();

        $response = $this->patchJson("/api/sales-orders/{$salesOrder->id}/status", ['status' => SalesOrder::STATUS_CANCELLED]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', SalesOrder::STATUS_CANCELLED);
    }

    public function test_cancelling_a_sales_order_with_a_recorded_payment_is_blocked(): void
    {
        $salesOrder = SalesOrder::factory()->status(SalesOrder::STATUS_CONFIRMED)->create(['total_amount' => 100]);
        Payment::factory()->create(['payable_id' => $salesOrder->id, 'payable_type' => 'sales_order', 'amount' => 10]);

        $response = $this->patchJson("/api/sales-orders/{$salesOrder->id}/status", ['status' => SalesOrder::STATUS_CANCELLED]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
        $this->assertSame(SalesOrder::STATUS_CONFIRMED, $salesOrder->fresh()->status);
    }

    public function test_allowed_next_statuses_reflect_the_current_status(): void
    {
        SalesOrder::factory()->status(SalesOrder::STATUS_DRAFT)->create();
        SalesOrder::factory()->status(SalesOrder::STATUS_FULFILLED)->create();

        $draftResponse = $this->getJson('/api/sales-orders?status=draft');
        $draftResponse->assertJsonPath('data.0.allowed_next_statuses', [SalesOrder::STATUS_PENDING, SalesOrder::STATUS_CANCELLED]);

        $fulfilledResponse = $this->getJson('/api/sales-orders?status=fulfilled');
        $fulfilledResponse->assertJsonPath('data.0.allowed_next_statuses', []);
    }
}
