<?php

namespace Database\Factories;

use App\AdStatus;
use App\CouponScope;
use App\CouponType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => Str::upper(fake()->unique()->bothify('??##??')),
            'description' => fake()->sentence(),
            'type' => CouponType::Percentage,
            'scope' => CouponScope::Global,
            'status' => AdStatus::Draft,
            'value' => fake()->numberBetween(5, 30),
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

    public function percentage(int $value = 10): static
    {
        return $this->state(fn (): array => [
            'type' => CouponType::Percentage,
            'value' => $value,
        ]);
    }

    public function fixedAmount(int $cents = 1000): static
    {
        return $this->state(fn (): array => [
            'type' => CouponType::FixedAmount,
            'value' => $cents,
        ]);
    }

    public function freeShipping(): static
    {
        return $this->state(fn (): array => [
            'type' => CouponType::FreeShipping,
            'value' => 0,
        ]);
    }

    public function forSector(string $sector): static
    {
        return $this->state(fn (): array => [
            'scope' => CouponScope::Sector,
            'sector' => $sector,
        ]);
    }

    public function withMaxUses(int $max = 100): static
    {
        return $this->state(fn (): array => [
            'max_uses' => $max,
        ]);
    }
}
