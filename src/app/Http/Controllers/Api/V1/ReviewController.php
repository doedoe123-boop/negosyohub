<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateReviewRequest;
use App\Models\Property;
use App\Models\Review;
use App\Models\Store;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Lunar\Models\Product;

/**
 * Customer-facing review endpoints for products and properties.
 *
 * Reviews are moderated: they are created unpublished (is_published = false)
 * and the store owner / admin can approve them from the Filament panel.
 */
class ReviewController extends Controller
{
    /**
     * List published reviews for a store.
     */
    public function storeIndex(Store $store): JsonResponse
    {
        abort_if(! $store->isApproved(), 404);

        $reviewQuery = Review::query()
            ->where('reviewable_type', Store::class)
            ->where('reviewable_id', $store->id)
            ->published();

        $reviews = (clone $reviewQuery)
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $reviews->getCollection()
                ->map(fn (Review $review): array => $this->formatReview($review))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
            'review_count' => (clone $reviewQuery)->count(),
            'average_rating' => (float) ((clone $reviewQuery)->avg('rating') ?: 0),
        ]);
    }

    /**
     * Submit a review for a store.
     */
    public function storeStore(CreateReviewRequest $request, Store $store): JsonResponse
    {
        abort_if(! $store->isApproved(), 404);

        $review = $this->createReview(
            $request,
            Store::class,
            $store->id,
            $store->id
        );

        return response()->json([
            'message' => 'Thank you! Your review has been submitted and is pending approval.',
            'review' => $this->formatReview($review),
        ], 201);
    }

    /**
     * List published reviews for a product.
     */
    public function productIndex(Product $product): JsonResponse
    {
        $reviews = Review::query()
            ->where('reviewable_type', Product::class)
            ->where('reviewable_id', $product->id)
            ->published()
            ->latest()
            ->paginate(10)
            ->through(fn (Review $r) => $this->formatReview($r));

        return response()->json($reviews);
    }

    /**
     * Submit a review for a product.
     */
    public function productStore(CreateReviewRequest $request, Product $product): JsonResponse
    {
        $storeId = $product->attribute_data->get('store_id')?->getValue();

        $review = $this->createReview(
            $request,
            Product::class,
            $product->id,
            $storeId
        );

        return response()->json([
            'message' => 'Thank you! Your review has been submitted and is pending approval.',
            'review' => $this->formatReview($review),
        ], 201);
    }

    /**
     * List published reviews for a property (using Testimonials).
     */
    public function propertyIndex(Property $property): JsonResponse
    {
        $reviews = Testimonial::query()
            ->where('property_id', $property->id)
            ->published()
            ->latest()
            ->paginate(10)
            ->through(fn (Testimonial $t) => [
                'id' => $t->id,
                'name' => $t->client_name,
                'rating' => $t->rating,
                'title' => $t->title,
                'content' => $t->content,
                'verified' => false,
                'date' => $t->created_at?->diffForHumans() ?? 'Recently',
            ]);

        return response()->json($reviews);
    }

    /**
     * Submit a review for a property (creates a Testimonial).
     */
    public function propertyStore(CreateReviewRequest $request, Property $property): JsonResponse
    {
        $user = auth()->user();

        // Prevent duplicate testimonials from the same user on the same property
        $existing = Testimonial::query()
            ->where('client_email', $user->email)
            ->where('property_id', $property->id)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'review' => 'You have already reviewed this item.',
            ]);
        }

        $testimonial = Testimonial::create([
            'store_id' => $property->store_id,
            'property_id' => $property->id,
            'client_name' => $user->name,
            'client_email' => $user->email,
            'rating' => $request->integer('rating'),
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'is_featured' => false,
            'is_published' => false, // Requires admin/store owner approval in the panel
        ]);

        return response()->json([
            'message' => 'Thank you! Your review has been submitted and is pending approval.',
            'review' => [
                'id' => $testimonial->id,
                'name' => $testimonial->client_name,
                'rating' => $testimonial->rating,
                'title' => $testimonial->title,
                'content' => $testimonial->content,
                'verified' => false,
                'date' => $testimonial->created_at?->diffForHumans() ?? 'Recently',
            ],
        ], 201);
    }

    /**
     * Create and persist a review.
     */
    private function createReview(
        CreateReviewRequest $request,
        string $reviewableType,
        int $reviewableId,
        ?int $storeId,
    ): Review {
        $user = auth()->user();

        // Prevent duplicate reviews from the same user on the same entity
        $existing = Review::query()
            ->where('user_id', $user->id)
            ->where('reviewable_type', $reviewableType)
            ->where('reviewable_id', $reviewableId)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'review' => 'You have already reviewed this item.',
            ]);
        }

        return Review::create([
            'store_id' => $storeId,
            'user_id' => $user->id,
            'reviewable_type' => $reviewableType,
            'reviewable_id' => $reviewableId,
            'reviewer_name' => $user->name,
            'reviewer_email' => $user->email,
            'rating' => $request->integer('rating'),
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'is_verified_purchase' => false,
            'is_published' => false,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatReview(Review $review): array
    {
        return [
            'id' => $review->id,
            'name' => $review->reviewer_name,
            'rating' => $review->rating,
            'title' => $review->title,
            'content' => $review->content,
            'verified' => $review->is_verified_purchase,
            'date' => $review->created_at?->diffForHumans() ?? 'Recently',
        ];
    }
}
