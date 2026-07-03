<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
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
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'quantity_on_hand' => $this->quantity_on_hand,
            'reorder_level' => $this->reorder_level,
            'unit' => $this->unit,
            'is_below_reorder_level' => $this->isBelowReorderLevel(),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
        ];
    }
}
