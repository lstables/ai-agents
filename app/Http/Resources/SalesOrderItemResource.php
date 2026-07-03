<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderItemResource extends JsonResource
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
            'description' => $this->description,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'line_total' => (float) $this->line_total,
            'inventory_item' => $this->whenLoaded('inventoryItem', fn () => $this->inventoryItem ? [
                'id' => $this->inventoryItem->id,
                'sku' => $this->inventoryItem->sku,
                'name' => $this->inventoryItem->name,
            ] : null),
        ];
    }
}
