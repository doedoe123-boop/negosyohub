<?php

namespace Database\Factories;

use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookEndpoint>
 */
class WebhookEndpointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => null,
            'name' => fake()->company().' Integration',
            'url' => fake()->url(),
            'secret' => fake()->sha256(),
            'events' => ['order.created', 'order.updated'],
            'is_active' => true,
        ];
    }
}
