<?php

namespace Database\Factories;

use App\CampaignStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => CampaignStatus::Draft,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'created_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => CampaignStatus::Active,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'status' => CampaignStatus::Scheduled,
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => CampaignStatus::Completed,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);
    }
}
