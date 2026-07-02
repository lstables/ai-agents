<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'created_by' => User::factory(),
            'reference' => 'PO-'.strtoupper(Str::random(8)),
            'status' => Purchase::STATUS_DRAFT,
            'order_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'expected_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
            'total_amount' => 0,
        ];
    }

    /**
     * Indicate the purchase has a specific status.
     */
    public function status(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
