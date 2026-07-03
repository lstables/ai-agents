<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['customer_id', 'created_by', 'reference', 'status', 'order_date', 'expected_date', 'notes', 'total_amount'])]
class SalesOrder extends Model
{
    /** @use HasFactory<\Database\Factories\SalesOrderFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_FULFILLED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * The sales order workflow: a linear progression toward "fulfilled",
     * with cancellation available from any non-terminal state. "fulfilled"
     * and "cancelled" are terminal — no further transitions are allowed
     * out of either.
     *
     * @return array<string, list<string>>
     */
    private static function transitionMap(): array
    {
        return [
            self::STATUS_DRAFT => [self::STATUS_PENDING, self::STATUS_CANCELLED],
            self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
            self::STATUS_CONFIRMED => [self::STATUS_FULFILLED, self::STATUS_CANCELLED],
            self::STATUS_FULFILLED => [],
            self::STATUS_CANCELLED => [],
        ];
    }

    /**
     * @return list<string>
     */
    public function allowedNextStatuses(): array
    {
        return self::transitionMap()[$this->status] ?? [];
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedNextStatuses(), true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<SalesOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Runs a live sum query rather than relying on an eager-loaded
     * aggregate. Deliberately simple for now — this means one query per
     * row when serializing a paginated list, accepted the same way
     * Reports' aggregation queries were: fine at this app's data volume,
     * worth revisiting if that changes.
     */
    public function amountPaid(): float
    {
        return round((float) $this->payments()->sum('amount'), 2);
    }

    public function balanceDue(): float
    {
        return max(0, round((float) $this->total_amount - $this->amountPaid(), 2));
    }
}
