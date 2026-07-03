<?php

namespace App\Actions\SalesOrders;

use App\Actions\Purchases\PurchaseTotalCalculator;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateSalesOrder
{
    /**
     * Create a sales order and its line items inside a single transaction,
     * deriving line and header totals from quantity/unit price rather than
     * trusting client-supplied totals. Reuses PurchaseTotalCalculator since
     * it is a generic quantity x price utility, not Purchase-specific.
     *
     * @param  array{customer_id: int, order_date: string, expected_date?: string|null, notes?: string|null, items: array<int, array{inventory_item_id?: int|null, description: string, quantity: float|string, unit_price: float|string}>}  $data
     */
    public function handle(array $data, User $creator): SalesOrder
    {
        return DB::transaction(function () use ($data, $creator) {
            $salesOrder = SalesOrder::create([
                'customer_id' => $data['customer_id'],
                'created_by' => $creator->id,
                'reference' => $this->generateReference(),
                'status' => SalesOrder::STATUS_DRAFT,
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

                $salesOrder->items()->create([
                    'inventory_item_id' => $item['inventory_item_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);

                $lineTotals[] = $lineTotal;
            }

            $salesOrder->update(['total_amount' => PurchaseTotalCalculator::total($lineTotals)]);

            return $salesOrder->fresh(['customer', 'items.inventoryItem']);
        });
    }

    private function generateReference(): string
    {
        do {
            $reference = 'SO-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (SalesOrder::where('reference', $reference)->exists());

        return $reference;
    }
}
