<?php

namespace Database\Factories;

use App\Models\MovingAddOn;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovingAddOn>
 */
class MovingAddOnFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory()->movingService(),
            'name' => fake()->randomElement([
                'Packing Materials',
                'Assembly/Disassembly Service',
                'Floor & Wall Protection',
                'Piano Moving',
                'Air Conditioning Unit Removal',
                'Extra Manpower',
                'Storage (1 week)',
                'Same-Day Rush Service',
            ]),
            'description' => fake()->optional(0.6)->sentence(),
            'price' => fake()->numberBetween(50000, 500000), // 500–5000 PHP in centavos
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function premium(): static
    {
        return $this->state(fn (): array => [
            'name' => fake()->randomElement([
                'Full Packing Crew',
                'Climate-Controlled Truck Upgrade',
                'Appliance Crating Service',
            ]),
            'price' => fake()->numberBetween(150000, 650000),
        ]);
    }
}
