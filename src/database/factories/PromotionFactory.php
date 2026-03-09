<?php

namespace Database\Factories;

use App\AdStatus;
use App\Models\User;
use App\PromotionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Promotion>
 */
class PromotionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(PromotionType::cases()),
            'status' => AdStatus::Draft,
            'discount_percentage' => fake()->numberBetween(5, 50),
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

    public function flashSale(): static
    {
        return $this->state(fn (): array => [
            'type' => PromotionType::FlashSale,
            'discount_percentage' => fake()->numberBetween(20, 70),
            'ends_at' => now()->addHours(24),
        ]);
    }

    public function holidaySale(): static
    {
        return $this->state(fn (): array => [
            'type' => PromotionType::HolidaySale,
        ]);
    }

    public function fixedAmount(): static
    {
        return $this->state(fn (): array => [
            'discount_percentage' => null,
            'discount_amount_cents' => fake()->numberBetween(100, 5000),
        ]);
    }
}
