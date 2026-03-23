<?php

use App\Models\Review;
use App\Models\Store;
use App\Models\User;
use App\StoreStatus;
use Illuminate\Support\Carbon;
use Lunar\Models\Product;

beforeEach(function () {
    $this->store = Store::factory()->create([
        'sector' => 'ecommerce',
    ]);
});

// =========================================================
// Review Model Basics
// =========================================================

it('creates a store review', function () {
    $review = Review::factory()->forStoreReview($this->store)->create([
        'reviewer_name' => 'Juan Dela Cruz',
        'rating' => 5,
    ]);

    expect($review)->toBeInstanceOf(Review::class)
        ->and($review->store_id)->toBe($this->store->id)
        ->and($review->reviewer_name)->toBe('Juan Dela Cruz')
        ->and($review->rating)->toBe(5)
        ->and($review->reviewable_type)->toBe(Store::class)
        ->and($review->reviewable_id)->toBe($this->store->id);
});

it('creates a product review with polymorphic type', function () {
    $review = Review::factory()->forProduct(99, $this->store->id)->create([
        'reviewer_name' => 'Maria Santos',
        'rating' => 4,
    ]);

    expect($review->reviewable_type)->toBe(Product::class)
        ->and($review->reviewable_id)->toBe(99)
        ->and($review->store_id)->toBe($this->store->id);
});

it('belongs to a store', function () {
    $review = Review::factory()->forStoreReview($this->store)->create();

    expect($review->store->id)->toBe($this->store->id);
});

it('optionally belongs to a user', function () {
    $user = User::factory()->create();
    $review = Review::factory()->forStoreReview($this->store)->create([
        'user_id' => $user->id,
    ]);

    expect($review->user->id)->toBe($user->id);
});

it('allows null user_id for guest reviews', function () {
    $review = Review::factory()->forStoreReview($this->store)->create([
        'user_id' => null,
    ]);

    expect($review->user)->toBeNull();
});

// =========================================================
// Scopes
// =========================================================

it('scopes reviews by store', function () {
    $otherStore = Store::factory()->create();

    Review::factory()->forStoreReview($this->store)->count(3)->create();
    Review::factory()->forStoreReview($otherStore)->count(2)->create();

    expect(Review::forStore($this->store->id)->count())->toBe(3);
});

it('scopes published reviews', function () {
    Review::factory()->forStoreReview($this->store)->published()->count(2)->create();
    Review::factory()->forStoreReview($this->store)->unpublished()->count(3)->create();

    expect(Review::published()->count())->toBe(2);
});

it('scopes featured reviews (must also be published)', function () {
    Review::factory()->forStoreReview($this->store)->featured()->count(2)->create();
    Review::factory()->forStoreReview($this->store)->published()->count(1)->create();
    Review::factory()->forStoreReview($this->store)->unpublished()->count(1)->create();

    expect(Review::featured()->count())->toBe(2);
});

it('scopes store reviews only', function () {
    Review::factory()->forStoreReview($this->store)->count(2)->create();
    Review::factory()->forProduct(1, $this->store->id)->count(3)->create();

    expect(Review::storeReviews()->count())->toBe(2);
});

it('scopes product reviews only', function () {
    Review::factory()->forStoreReview($this->store)->count(2)->create();
    Review::factory()->forProduct(1, $this->store->id)->count(3)->create();

    expect(Review::productReviews()->count())->toBe(3);
});

it('scopes verified purchase reviews', function () {
    Review::factory()->forStoreReview($this->store)->verifiedPurchase()->count(2)->create();
    Review::factory()->forStoreReview($this->store)->create(['is_verified_purchase' => false]);

    expect(Review::verifiedPurchase()->count())->toBe(2);
});

it('combines multiple scopes', function () {
    Review::factory()->forStoreReview($this->store)->published()->verifiedPurchase()->count(2)->create();
    Review::factory()->forStoreReview($this->store)->published()->create(['is_verified_purchase' => false]);
    Review::factory()->forStoreReview($this->store)->unpublished()->verifiedPurchase()->create();

    expect(Review::forStore($this->store->id)->published()->verifiedPurchase()->count())->toBe(2);
});

// =========================================================
// Helpers
// =========================================================

it('publishes a review', function () {
    $review = Review::factory()->forStoreReview($this->store)->unpublished()->create();

    expect($review->is_published)->toBeFalse();

    $review->publish();
    $review->refresh();

    expect($review->is_published)->toBeTrue()
        ->and($review->published_at)->not->toBeNull();
});

it('unpublishes a review', function () {
    $review = Review::factory()->forStoreReview($this->store)->published()->create();

    expect($review->is_published)->toBeTrue();

    $review->unpublish();
    $review->refresh();

    expect($review->is_published)->toBeFalse();
});

it('generates a star rating string', function () {
    $review = Review::factory()->forStoreReview($this->store)->create(['rating' => 4]);

    expect($review->starRating())->toBe('★★★★☆');
});

it('generates full stars for rating 5', function () {
    $review = Review::factory()->forStoreReview($this->store)->create(['rating' => 5]);

    expect($review->starRating())->toBe('★★★★★');
});

it('generates single star for rating 1', function () {
    $review = Review::factory()->forStoreReview($this->store)->create(['rating' => 1]);

    expect($review->starRating())->toBe('★☆☆☆☆');
});

it('detects a store review', function () {
    $review = Review::factory()->forStoreReview($this->store)->create();

    expect($review->isStoreReview())->toBeTrue()
        ->and($review->isProductReview())->toBeFalse();
});

it('detects a product review', function () {
    $review = Review::factory()->forProduct(1, $this->store->id)->create();

    expect($review->isProductReview())->toBeTrue()
        ->and($review->isStoreReview())->toBeFalse();
});

// =========================================================
// Store → Reviews relationship
// =========================================================

it('accesses reviews through the store model', function () {
    Review::factory()->forStoreReview($this->store)->count(3)->create();

    expect($this->store->reviews)->toHaveCount(3)
        ->and($this->store->reviews->first())->toBeInstanceOf(Review::class);
});

it('calculates average review rating for a store', function () {
    Review::factory()->forStoreReview($this->store)->published()->create(['rating' => 5]);
    Review::factory()->forStoreReview($this->store)->published()->create(['rating' => 3]);
    Review::factory()->forStoreReview($this->store)->unpublished()->create(['rating' => 1]);

    // Only published reviews count: (5 + 3) / 2 = 4.0
    expect($this->store->averageReviewRating())->toBe(4.0);
});

it('returns zero average when no published reviews exist', function () {
    Review::factory()->forStoreReview($this->store)->unpublished()->count(2)->create();

    expect($this->store->averageReviewRating())->toBe(0.0);
});

// =========================================================
// Store Review API
// =========================================================

it('lists published store reviews for the storefront API', function () {
    Review::factory()->forStoreReview($this->store)->published()->create([
        'rating' => 5,
        'reviewer_name' => 'Ana Reyes',
        'created_at' => now()->subDay(),
    ]);
    Review::factory()->forStoreReview($this->store)->published()->create([
        'rating' => 3,
        'reviewer_name' => 'Marco Dizon',
        'created_at' => now(),
    ]);
    Review::factory()->forStoreReview($this->store)->unpublished()->create([
        'rating' => 1,
    ]);

    $response = $this->getJson("/api/v1/stores/{$this->store->slug}/reviews");

    $response->assertOk()
        ->assertJsonPath('review_count', 2)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Marco Dizon')
        ->assertJsonPath('data.1.name', 'Ana Reyes');

    expect((float) $response->json('average_rating'))->toBe(4.0);
});

it('returns 404 for unapproved store reviews API access', function () {
    $pendingStore = Store::factory()->create([
        'status' => StoreStatus::Pending,
        'sector' => 'ecommerce',
    ]);

    $this->getJson("/api/v1/stores/{$pendingStore->slug}/reviews")
        ->assertNotFound();
});

it('allows an authenticated customer to submit a store review', function () {
    $customer = User::factory()->create();

    $response = $this->actingAs($customer)
        ->postJson("/api/v1/stores/{$this->store->slug}/reviews", [
            'rating' => 5,
            'title' => 'Excellent service',
            'content' => 'Fast shipping, responsive support, and great packaging.',
        ]);

    $response->assertCreated()
        ->assertJsonPath('review.name', $customer->name)
        ->assertJsonPath('review.rating', 5);

    $this->assertDatabaseHas('reviews', [
        'store_id' => $this->store->id,
        'user_id' => $customer->id,
        'reviewable_type' => Store::class,
        'reviewable_id' => $this->store->id,
        'rating' => 5,
        'title' => 'Excellent service',
        'is_published' => false,
    ]);
});

it('prevents duplicate store reviews from the same user', function () {
    $customer = User::factory()->create();

    Review::factory()->forStoreReview($this->store)->create([
        'user_id' => $customer->id,
        'reviewable_type' => Store::class,
        'reviewable_id' => $this->store->id,
    ]);

    $this->actingAs($customer)
        ->postJson("/api/v1/stores/{$this->store->slug}/reviews", [
            'rating' => 4,
            'content' => 'Trying to review the same store again.',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('review');
});

// =========================================================
// Factory States
// =========================================================

it('creates review with published state', function () {
    $review = Review::factory()->forStoreReview($this->store)->published()->create();

    expect($review->is_published)->toBeTrue()
        ->and($review->published_at)->not->toBeNull();
});

it('creates review with featured state', function () {
    $review = Review::factory()->forStoreReview($this->store)->featured()->create();

    expect($review->is_featured)->toBeTrue()
        ->and($review->is_published)->toBeTrue();
});

it('creates review with verified purchase state', function () {
    $review = Review::factory()->forStoreReview($this->store)->verifiedPurchase()->create();

    expect($review->is_verified_purchase)->toBeTrue();
});

// =========================================================
// Casts
// =========================================================

it('casts boolean fields correctly', function () {
    $review = Review::factory()->forStoreReview($this->store)->create([
        'is_verified_purchase' => true,
        'is_published' => true,
        'is_featured' => false,
    ]);

    expect($review->is_verified_purchase)->toBeTrue()->toBeBool()
        ->and($review->is_published)->toBeTrue()->toBeBool()
        ->and($review->is_featured)->toBeFalse()->toBeBool();
});

it('casts rating as integer', function () {
    $review = Review::factory()->forStoreReview($this->store)->create(['rating' => 4]);

    expect($review->rating)->toBeInt()->toBe(4);
});

it('casts published_at as datetime', function () {
    $review = Review::factory()->forStoreReview($this->store)->published()->create();

    expect($review->published_at)->toBeInstanceOf(Carbon::class);
});
