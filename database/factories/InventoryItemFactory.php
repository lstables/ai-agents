<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => 'SKU-'.strtoupper(Str::random(8)),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'quantity_on_hand' => fake()->numberBetween(0, 200),
            'reorder_level' => fake()->numberBetween(5, 20),
            'unit' => fake()->randomElement(['each', 'kg', 'box']),
        ];
    }

    /**
     * Indicate the item's stock is at or below its reorder level.
     */
    public function belowReorderLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'reorder_level' => 10,
            'quantity_on_hand' => fake()->numberBetween(0, 10),
        ]);
    }
}
