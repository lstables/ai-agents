<?php

namespace App\Http\Resources;

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
            // payable_type is already the short alias ("purchase" /
            // "sales_order"), not a raw class name, since it's read back
            // through the enforced morph map — no need to re-derive it
            // from get_class($this->payable) and duplicate that mapping.
            'payable' => $this->whenLoaded('payable', fn () => [
                'type' => $this->payable_type,
                'id' => $this->payable->id,
                'reference' => $this->payable->reference,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
