<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sector>
 */
class SectorFactory extends Factory
{
    protected $model = \App\Models\Sector::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'icon' => 'heroicon-o-building-storefront',
            'color' => fake()->randomElement(['indigo', 'emerald', 'amber', 'rose', 'sky', 'violet']),
            'registration_button_text' => null,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }

    /**
     * Mark the sector as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
