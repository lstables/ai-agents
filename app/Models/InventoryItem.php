<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['sku', 'name', 'description', 'quantity_on_hand', 'reorder_level', 'unit', 'supplier_id'])]
class InventoryItem extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return HasMany<SalesOrderItem, $this>
     */
    public function salesOrderItems(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

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
