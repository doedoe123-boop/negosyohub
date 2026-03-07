<?php

namespace Database\Factories;

use App\Models\MovingBooking;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MovingReview>
 */
class MovingReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'moving_booking_id' => MovingBooking::factory(),
            'store_id' => Store::factory(),
            'customer_user_id' => User::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->optional()->sentences(2, true),
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }
}
