<?php

namespace Database\Factories;

use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\WebhookDeliveryStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'webhook_endpoint_id' => WebhookEndpoint::factory(),
            'event' => 'order.created',
            'delivery_status' => WebhookDeliveryStatus::Pending,
            'payload' => ['id' => fake()->uuid()],
            'attempts' => 0,
        ];
    }
}
