<?php

namespace App\Actions\Purchases;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatePurchase
{
    /**
     * Create a purchase and its line items inside a single transaction,
     * deriving line and header totals from quantity/unit price rather
     * than trusting client-supplied totals.
     *
     * @param  array{supplier_id: int, order_date: string, expected_date?: string|null, notes?: string|null, items: array<int, array{description: string, quantity: float|string, unit_price: float|string}>}  $data
     */
    public function handle(array $data, User $creator): Purchase
    {
        return DB::transaction(function () use ($data, $creator) {
            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'created_by' => $creator->id,
                'reference' => $this->generateReference(),
                'status' => Purchase::STATUS_DRAFT,
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'total_amount' => 0,
            ]);

            $lineTotals = [];

            foreach ($data['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $lineTotal = PurchaseTotalCalculator::lineTotal($quantity, $unitPrice);

                $purchase->items()->create([
                    'description' => $item['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);

                $lineTotals[] = $lineTotal;
            }

            $purchase->update(['total_amount' => PurchaseTotalCalculator::total($lineTotals)]);

            return $purchase->fresh(['supplier', 'items']);
        });
    }

    private function generateReference(): string
    {
        do {
            $reference = 'PO-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Purchase::where('reference', $reference)->exists());

        return $reference;
    }
}
