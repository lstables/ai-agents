<?php

namespace Tests\Unit;

use App\Actions\Purchases\PurchaseTotalCalculator;
use PHPUnit\Framework\TestCase;

class PurchaseTotalCalculatorTest extends TestCase
{
    public function test_line_total_multiplies_quantity_by_unit_price(): void
    {
        $this->assertSame(20.0, PurchaseTotalCalculator::lineTotal(2, 10));
    }

    public function test_line_total_rounds_to_two_decimal_places(): void
    {
        $this->assertSame(3.33, PurchaseTotalCalculator::lineTotal(3, 1.111));
    }

    public function test_total_sums_line_totals(): void
    {
        $this->assertSame(46.5, PurchaseTotalCalculator::total([20.0, 26.5]));
    }

    public function test_total_of_no_items_is_zero(): void
    {
        $this->assertSame(0.0, PurchaseTotalCalculator::total([]));
    }

    public function test_total_rounds_the_summed_result(): void
    {
        $this->assertSame(0.3, PurchaseTotalCalculator::total([0.1, 0.1, 0.1]));
    }
}
