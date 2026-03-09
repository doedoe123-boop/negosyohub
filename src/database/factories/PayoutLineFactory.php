<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PayoutLine>
 */
class PayoutLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payout_id' => \App\Models\Payout::factory(),
            'order_id' => \App\Models\Order::factory(),
            'store_earning' => fake()->numberBetween(1000, 100000),
        ];
    }
}
