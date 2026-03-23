<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'paymongo_id' => 'pm_'.fake()->unique()->bothify('demo########'),
            'paymongo_customer_id' => 'cus_'.fake()->bothify('demo######'),
            'brand' => fake()->randomElement(['Visa', 'Mastercard']),
            'last4' => fake()->numerify('####'),
            'exp_month' => fake()->numberBetween(1, 12),
            'exp_year' => (int) now()->addYears(fake()->numberBetween(1, 5))->format('Y'),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (): array => [
            'is_default' => true,
        ]);
    }
}
