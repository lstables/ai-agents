<?php

namespace App\Actions\Purchases;

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePurchaseStatus
{
    /**
     * Re-checks the transition (and, for cancellation, the payment guard)
     * against a locked row inside a transaction. UpdatePurchaseStatusRequest
     * already performs the same checks for fast, clear validation feedback
     * in the common case, but that check runs before any lock is held —
     * this re-check is the authoritative guard, matching the pattern used
     * for the payment overpayment guard (see PaymentController::store).
     */
    public function handle(Purchase $purchase, string $status): Purchase
    {
        return DB::transaction(function () use ($purchase, $status) {
            $locked = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if (! $locked->canTransitionTo($status)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot move a purchase from \"{$locked->status}\" to \"{$status}\".",
                ]);
            }

            if ($status === Purchase::STATUS_CANCELLED && $locked->payments()->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Cannot cancel a purchase that already has recorded payments.',
                ]);
            }

            $locked->update(['status' => $status]);

            return $locked->fresh(['supplier', 'items']);
        });
    }
}
