<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RentalAgreement>
 */
class RentalAgreementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'store_id' => Store::factory(),
            'tenant_user_id' => null,
            'tenant_name' => fake()->name(),
            'tenant_email' => fake()->safeEmail(),
            'tenant_phone' => fake()->optional(0.7)->numerify('09#########'),
            'monthly_rent' => fake()->numberBetween(8_000_00, 80_000_00), // cents
            'security_deposit' => fake()->optional(0.8)->numberBetween(8_000_00, 160_000_00),
            'move_in_date' => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'lease_term_months' => fake()->optional(0.7)->randomElement([6, 12, 24]),
            'notes' => fake()->optional(0.4)->paragraph(),
            'status' => 'pending',
            'tenant_questions' => null,
            'landlord_response' => null,
            'signed_at' => null,
        ];
    }

    /**
     * Link this agreement to an existing tenant user account.
     */
    public function withTenantUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_user_id' => User::factory(),
        ]);
    }
}
