<?php

namespace Database\Factories;

use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SectorDocument>
 */
class SectorDocumentFactory extends Factory
{
    protected $model = \App\Models\SectorDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sector_id' => Sector::factory(),
            'key' => fake()->unique()->slug(2),
            'label' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'is_required' => fake()->boolean(70),
            'mimes' => 'pdf,jpg,png',
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }

    /**
     * Mark as required document.
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }
}
