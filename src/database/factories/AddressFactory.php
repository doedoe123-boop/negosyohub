<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
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
            'label' => fake()->randomElement(['Home', 'Office', 'Condo', 'Parents']),
            'line1' => fake()->streetAddress(),
            'line2' => fake()->optional(0.35)->secondaryAddress(),
            'barangay' => 'Barangay '.fake()->randomElement(['Bel-Air', 'San Antonio', 'Poblacion', 'Ugong', 'Lahug']),
            'city' => fake()->randomElement(['Makati City', 'Quezon City', 'Pasig City', 'Cebu City', 'Davao City']),
            'province' => fake()->randomElement(['Metro Manila', 'Cebu', 'Davao del Sur']),
            'postal_code' => fake()->numerify('####'),
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
