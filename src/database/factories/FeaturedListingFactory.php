<?php

namespace Database\Factories;

use App\AdStatus;
use App\FeaturedType;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeaturedListing>
 */
class FeaturedListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'featured_type' => FeaturedType::Store,
            'featurable_type' => Store::class,
            'featurable_id' => Store::factory(),
            'status' => AdStatus::Draft,
            'priority' => fake()->numberBetween(0, 10),
            'cost_cents' => fake()->numberBetween(1000, 50000),
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'created_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => AdStatus::Active,
        ]);
    }

    public function featuredStore(): static
    {
        return $this->state(fn (): array => [
            'featured_type' => FeaturedType::Store,
            'featurable_type' => Store::class,
            'featurable_id' => Store::factory(),
        ]);
    }

    public function featuredProduct(): static
    {
        return $this->state(fn (): array => [
            'featured_type' => FeaturedType::Product,
        ]);
    }

    public function featuredService(): static
    {
        return $this->state(fn (): array => [
            'featured_type' => FeaturedType::Service,
        ]);
    }
}
