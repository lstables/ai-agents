<?php

namespace App\Actions\Purchases;

class PurchaseTotalCalculator
{
    /**
     * Calculate a single line item's total, rounded to 2 decimal places.
     */
    public static function lineTotal(float $quantity, float $unitPrice): float
    {
        return round($quantity * $unitPrice, 2);
    }

    /**
     * Sum a list of line totals into a purchase total, rounded to 2 decimal places.
     *
     * @param  array<int, float>  $lineTotals
     */
    public static function total(array $lineTotals): float
    {
        return round(array_sum($lineTotals), 2);
    }
}
