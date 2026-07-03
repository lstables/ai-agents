<?php

namespace App\Http\Resources;

use App\Models\Purchase;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'payment_date' => $this->payment_date?->toDateString(),
            'method' => $this->method,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'payable' => $this->whenLoaded('payable', fn () => [
                'type' => match (get_class($this->payable)) {
                    Purchase::class => 'purchase',
                    SalesOrder::class => 'sales_order',
                    default => null,
                },
                'id' => $this->payable->id,
                'reference' => $this->payable->reference,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
