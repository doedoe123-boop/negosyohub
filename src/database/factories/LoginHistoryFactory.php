<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoginHistory>
 */
class LoginHistoryFactory extends Factory
{
    protected $model = \App\Models\LoginHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'email' => fake()->safeEmail(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'status' => 'success',
        ];
    }

    /**
     * Mark as a failed login attempt.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failure',
            'user_id' => null,
        ]);
    }
}
