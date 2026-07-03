<?php

namespace App\Actions\SalesOrders;

use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSalesOrderStatus
{
    /**
     * Re-checks the transition (and, for cancellation, the payment guard)
     * against a locked row inside a transaction. UpdateSalesOrderStatusRequest
     * already performs the same checks for fast, clear validation feedback
     * in the common case, but that check runs before any lock is held —
     * this re-check is the authoritative guard, matching the pattern used
     * for the payment overpayment guard (see PaymentController::store).
     */
    public function handle(SalesOrder $salesOrder, string $status): SalesOrder
    {
        return DB::transaction(function () use ($salesOrder, $status) {
            $locked = SalesOrder::query()->whereKey($salesOrder->id)->lockForUpdate()->firstOrFail();

            if (! $locked->canTransitionTo($status)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot move a sales order from \"{$locked->status}\" to \"{$status}\".",
                ]);
            }

            if ($status === SalesOrder::STATUS_CANCELLED && $locked->payments()->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Cannot cancel a sales order that already has recorded payments.',
                ]);
            }

            $locked->update(['status' => $status]);

            return $locked->fresh(['customer', 'items']);
        });
    }
}
