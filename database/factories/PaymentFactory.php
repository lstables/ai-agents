<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payable_id' => Purchase::factory(),
            // The morph map (see AppServiceProvider) enforces the short
            // alias, not the raw class name, as the payable_type value.
            'payable_type' => 'purchase',
            'created_by' => User::factory(),
            'amount' => fake()->randomFloat(2, 1, 100),
            'payment_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'method' => fake()->randomElement(['bank_transfer', 'card', 'cash']),
            'reference' => 'PMT-'.strtoupper(fake()->bothify('??####')),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
