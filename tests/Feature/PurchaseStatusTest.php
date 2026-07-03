<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_purchase_can_move_through_each_legal_transition(): void
    {
        $purchase = Purchase::factory()->status(Purchase::STATUS_DRAFT)->create();

        foreach ([Purchase::STATUS_PENDING, Purchase::STATUS_APPROVED, Purchase::STATUS_RECEIVED] as $nextStatus) {
            $response = $this->patchJson("/api/purchases/{$purchase->id}/status", ['status' => $nextStatus]);

            $response->assertStatus(200);
            $response->assertJsonPath('data.status', $nextStatus);
            $this->assertSame($nextStatus, $purchase->fresh()->status);
        }
    }

    public function test_a_purchase_can_be_cancelled_from_any_non_terminal_status(): void
    {
        foreach ([Purchase::STATUS_DRAFT, Purchase::STATUS_PENDING, Purchase::STATUS_APPROVED] as $status) {
            $purchase = Purchase::factory()->status($status)->create();

            $response = $this->patchJson("/api/purchases/{$purchase->id}/status", ['status' => Purchase::STATUS_CANCELLED]);

            $response->assertStatus(200);
            $response->assertJsonPath('data.status', Purchase::STATUS_CANCELLED);
        }
    }

    public function test_a_skipped_transition_is_rejected(): void
    {
        $purchase = Purchase::factory()->status(Purchase::STATUS_DRAFT)->create();

        $response = $this->patchJson("/api/purchases/{$purchase->id}/status", ['status' => Purchase::STATUS_RECEIVED]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
        $this->assertSame(Purchase::STATUS_DRAFT, $purchase->fresh()->status);
    }

    public function test_transitioning_to_the_same_status_is_rejected(): void
    {
        $purchase = Purchase::factory()->status(Purchase::STATUS_PENDING)->create();

        $response = $this->patchJson("/api/purchases/{$purchase->id}/status", ['status' => Purchase::STATUS_PENDING]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_a_terminal_status_cannot_transition_further(): void
    {
        $received = Purchase::factory()->status(Purchase::STATUS_RECEIVED)->create();
        $cancelled = Purchase::factory()->status(Purchase::STATUS_CANCELLED)->create();

        $this->patchJson("/api/purchases/{$received->id}/status", ['status' => Purchase::STATUS_CANCELLED])
            ->assertStatus(422);
        $this->patchJson("/api/purchases/{$cancelled->id}/status", ['status' => Purchase::STATUS_DRAFT])
            ->assertStatus(422);
    }

    public function test_an_invalid_status_value_is_rejected(): void
    {
        $purchase = Purchase::factory()->status(Purchase::STATUS_DRAFT)->create();

        $response = $this->patchJson("/api/purchases/{$purchase->id}/status", ['status' => 'not-a-real-status']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_cancelling_a_purchase_with_no_payments_succeeds(): void
    {
        $purchase = Purchase::factory()->status(Purchase::STATUS_APPROVED)->create();

        $response = $this->patchJson("/api/purchases/{$purchase->id}/status", ['status' => Purchase::STATUS_CANCELLED]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', Purchase::STATUS_CANCELLED);
    }

    public function test_cancelling_a_purchase_with_a_recorded_payment_is_blocked(): void
    {
        $purchase = Purchase::factory()->status(Purchase::STATUS_APPROVED)->create(['total_amount' => 100]);
        Payment::factory()->create(['payable_id' => $purchase->id, 'payable_type' => 'purchase', 'amount' => 10]);

        $response = $this->patchJson("/api/purchases/{$purchase->id}/status", ['status' => Purchase::STATUS_CANCELLED]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
        $this->assertSame(Purchase::STATUS_APPROVED, $purchase->fresh()->status);
    }

    public function test_allowed_next_statuses_reflect_the_current_status(): void
    {
        $draft = Purchase::factory()->status(Purchase::STATUS_DRAFT)->create();
        $received = Purchase::factory()->status(Purchase::STATUS_RECEIVED)->create();

        $draftResponse = $this->getJson('/api/purchases?status=draft');
        $draftResponse->assertJsonPath('data.0.allowed_next_statuses', [Purchase::STATUS_PENDING, Purchase::STATUS_CANCELLED]);

        $receivedResponse = $this->getJson('/api/purchases?status=received');
        $receivedResponse->assertJsonPath('data.0.allowed_next_statuses', []);
    }
}
