<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['sku', 'name', 'description', 'quantity_on_hand', 'reorder_level', 'unit'])]
class InventoryItem extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryItemFactory> */
    use HasFactory;

    /**
     * Scope a query to items at or below their reorder level. Items
     * without a reorder level set are never considered low on stock.
     *
     * @param  Builder<InventoryItem>  $query
     * @return Builder<InventoryItem>
     */
    public function scopeBelowReorderLevel(Builder $query): Builder
    {
        return $query->whereNotNull('reorder_level')
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level');
    }

    public function isBelowReorderLevel(): bool
    {
        return $this->reorder_level !== null && $this->quantity_on_hand <= $this->reorder_level;
    }
}
