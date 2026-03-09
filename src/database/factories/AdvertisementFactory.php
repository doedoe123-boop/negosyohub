<?php

namespace Database\Factories;

use App\AdPlacement;
use App\AdStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Advertisement>
 */
class AdvertisementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'placement' => fake()->randomElement(AdPlacement::cases()),
            'status' => AdStatus::Draft,
            'image_url' => fake()->imageUrl(1200, 400),
            'link_url' => fake()->url(),
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

    public function homeBanner(): static
    {
        return $this->state(fn (): array => [
            'placement' => AdPlacement::HomeBanner,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => AdStatus::Expired,
            'ends_at' => now()->subDay(),
        ]);
    }
}
