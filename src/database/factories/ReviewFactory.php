<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Product;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Review $review): void {
            if (! $review->reviewable_type) {
                $review->reviewable_type = Store::class;
            }
        })->afterCreating(function (Review $review): void {
            if ($review->reviewable_type === Store::class && ! $review->reviewable_id) {
                $review->updateQuietly([
                    'reviewable_id' => $review->store_id,
                ]);
            }
        });
    }

    /**
     * @var list<string>
     */
    private array $reviewTitles = [
        'Great experience!',
        'Loved the products',
        'Fast delivery and good quality',
        'Could be better',
        'Excellent customer service',
        'Best store in the area',
        'Amazing food quality',
        'Will definitely order again',
        'Good value for money',
        'Highly recommended!',
    ];

    /**
     * @var list<string>
     */
    private array $reviewContents = [
        'The quality of the products exceeded my expectations. Everything was fresh and well-packed.',
        'I had a wonderful experience ordering from this store. The delivery was quick and the staff was very friendly.',
        'Good products overall. A few items were not available but the rest were great.',
        'The prices are very reasonable for the quality you get. I will be a regular customer for sure.',
        'Outstanding service! They even included a handwritten thank-you note with my order.',
        'The food was delicious and arrived still hot. Portions were generous too.',
        'Decent experience. The products are good but delivery took a bit longer than expected.',
        'I love that they use eco-friendly packaging. Great products and great values.',
        'My go-to store for weekly groceries. Never disappointed with the quality.',
        'The variety of products available is impressive. Found everything I was looking for.',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'user_id' => null,
            'reviewable_type' => Store::class,
            'reviewable_id' => null,
            'reviewer_name' => fake()->name(),
            'reviewer_email' => fake()->safeEmail(),
            'rating' => fake()->numberBetween(3, 5),
            'title' => fake()->randomElement($this->reviewTitles),
            'content' => fake()->randomElement($this->reviewContents),
            'is_verified_purchase' => fake()->boolean(40),
            'is_published' => false,
            'is_featured' => false,
            'published_at' => null,
        ];
    }

    /**
     * Review for a specific store (store review, not product).
     */
    public function forStoreReview(Store $store): static
    {
        return $this->state(fn (array $attributes) => [
            'store_id' => $store->id,
            'reviewable_type' => Store::class,
            'reviewable_id' => $store->id,
        ]);
    }

    /**
     * Review for a Lunar product.
     */
    public function forProduct(int $productId, int $storeId): static
    {
        return $this->state(fn (array $attributes) => [
            'store_id' => $storeId,
            'reviewable_type' => Product::class,
            'reviewable_id' => $productId,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function featured(): static
    {
        return $this->published()->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function verifiedPurchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified_purchase' => true,
        ]);
    }
}
