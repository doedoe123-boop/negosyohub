<?php

namespace Database\Factories;

use App\DeliveryStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\ShipmentProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => ShipmentProvider::Manual,
            'delivery_status' => DeliveryStatus::Pending,
            'driver_name' => fake()->name(),
            'driver_contact' => fake()->phoneNumber(),
            'vehicle_type' => fake()->randomElement(['motorcycle', 'sedan', 'van']),
            'tracking_url' => fake()->optional()->url(),
            'pickup_address' => fake()->streetAddress(),
            'dropoff_address' => fake()->streetAddress(),
        ];
    }
}
