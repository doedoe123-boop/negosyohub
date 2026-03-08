<?php

namespace Database\Factories;

use App\Models\Sector;
use App\Models\User;
use App\StoreStatus;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => UserRole::StoreOwner]),
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'login_token' => 'stk_'.Str::random(24),
            'description' => fake()->sentence(),
            'commission_rate' => 15.00,
            'status' => StoreStatus::Approved,
            'sector' => fn () => Sector::inRandomOrder()->value('slug') ?? 'ecommerce',
            'address' => [
                'line_one' => fake()->streetAddress(),
                'city' => fake()->city(),
                'postcode' => fake()->postcode(),
            ],
        ];
    }

    /**
     * Indicate the store is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StoreStatus::Pending,
            'login_token' => null,
        ]);
    }

    /**
     * Indicate the store is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StoreStatus::Suspended,
            'suspended_at' => now(),
            'suspension_reason' => 'Terms violation',
        ]);
    }

    /**
     * Set a specific sector slug for the store.
     */
    public function sector(string $sectorSlug): static
    {
        return $this->state(fn (array $attributes) => [
            'sector' => $sectorSlug,
        ]);
    }
}
