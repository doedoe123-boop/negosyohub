<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyAnalytic;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyAnalytic>
 */
class PropertyAnalyticFactory extends Factory
{
    protected $model = PropertyAnalytic::class;

    public function definition(): array
    {
        $store = Store::factory()->create(['sector' => 'real_estate']);

        return [
            'property_id' => Property::factory()->for($store),
            'store_id' => $store->id,
            'date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'views' => fake()->numberBetween(0, 200),
            'unique_views' => fn (array $attrs) => fake()->numberBetween(0, $attrs['views']),
            'inquiries' => fake()->numberBetween(0, 10),
            'phone_clicks' => fake()->numberBetween(0, 20),
            'email_clicks' => fake()->numberBetween(0, 15),
            'share_clicks' => fake()->numberBetween(0, 8),
        ];
    }
}
