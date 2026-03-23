<?php

namespace Database\Factories;

use App\Models\MovingBooking;
use App\Models\RentalAgreement;
use App\Models\Store;
use App\Models\User;
use App\MovingBookingStatus;
use App\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovingBooking>
 */
class MovingBookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cities = ['Makati', 'Quezon City', 'Cebu City', 'Davao City', 'Manila', 'Taguig', 'Pasig'];

        $basePrice = fake()->numberBetween(200000, 2000000); // 2000–20000 PHP

        return [
            'store_id' => Store::factory()->movingService(),
            'customer_user_id' => User::factory(),
            'rental_agreement_id' => null,
            'status' => MovingBookingStatus::Pending,
            'pickup_address' => fake()->streetAddress(),
            'delivery_address' => fake()->streetAddress(),
            'pickup_city' => fake()->randomElement($cities),
            'delivery_city' => fake()->randomElement($cities),
            'scheduled_at' => fake()->dateTimeBetween('now', '+30 days'),
            'contact_name' => fake()->name(),
            'contact_phone' => fake()->numerify('09#########'),
            'notes' => fake()->optional(0.4)->sentence(),
            'base_price' => $basePrice,
            'add_ons_total' => 0,
            'total_price' => $basePrice,
            'payment_status' => PaymentStatus::Pending,
            'paymongo_payment_intent_id' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MovingBookingStatus::Confirmed,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MovingBookingStatus::Completed,
            'payment_status' => PaymentStatus::Paid,
        ]);
    }

    public function linkedToRental(): static
    {
        return $this->state(fn (): array => [
            'rental_agreement_id' => RentalAgreement::factory()->withTenantUser(),
        ]);
    }
}
