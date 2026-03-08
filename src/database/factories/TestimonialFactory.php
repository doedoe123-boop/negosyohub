<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    /** @var list<string> */
    private array $reviews = [
        'Amazing service! Found our dream home in just two weeks.',
        'Very professional and responsive agent. Highly recommended!',
        'The whole buying process was smooth and stress-free.',
        'Great knowledge of the local market. Helped us negotiate a fair price.',
        'Excellent communication throughout the entire transaction.',
        'They went above and beyond to help us find the perfect property.',
        'Best real estate experience we have ever had. Five stars!',
        'Patient, knowledgeable, and truly cares about their clients.',
        'Sold our property above asking price. Outstanding results!',
        'Made the rental process quick and painless. Thank you!',
    ];

    public function definition(): array
    {
        return [
            'store_id' => Store::factory()->create(['sector' => 'real_estate'])->id,
            'property_id' => null,
            'client_name' => fake()->name(),
            'client_email' => fake()->optional(0.5)->safeEmail(),
            'client_photo' => null,
            'rating' => fake()->numberBetween(3, 5),
            'content' => fake()->randomElement($this->reviews),
            'is_featured' => fake()->boolean(20),
            'is_published' => fake()->boolean(70),
            'published_at' => fn (array $attrs) => $attrs['is_published'] ? fake()->dateTimeBetween('-6 months', 'now') : null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn () => [
            'is_featured' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
