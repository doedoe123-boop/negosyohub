<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\QuickInquiryRequest;
use App\Http\Requests\Api\V1\SubmitInquiryRequest;
use App\Http\Resources\Api\V1\PropertyDetailResource;
use App\Http\Resources\Api\V1\PropertyResource;
use App\Models\Property;
use App\Services\PropertyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read-only property browsing endpoints for the customer storefront.
 *
 * Properties belong to real estate stores and are managed in the Realty panel.
 * This controller surfaces only active, published listings.
 */
class PropertyController extends Controller
{
    public function __construct(
        private PropertyService $propertyService
    ) {}

    /**
     * List active published properties with optional filters.
     *
     * Supported query params:
     *   search, type, listing_type, min_price, max_price, bedrooms, city, featured, per_page
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return PropertyResource::collection(
            $this->propertyService->browse(
                $request->only([
                    'search',
                    'type',
                    'listing_type',
                    'min_price',
                    'max_price',
                    'bedrooms',
                    'city',
                    'featured',
                    'per_page',
                ])
            )
        );
    }

    /**
     * Show a single active property by slug, incrementing its view counter.
     */
    public function show(string $slug): PropertyDetailResource
    {
        return new PropertyDetailResource(
            $this->propertyService->findBySlugOrFail($slug)
        );
    }

    /**
     * Submit a customer inquiry for a property.
     *
     * Public — no authentication required.
     */
    public function submitInquiry(SubmitInquiryRequest $request, Property $property): JsonResponse
    {
        $inquiry = $this->propertyService->submitInquiry($property, $request->validated());

        return response()->json([
            'message' => 'Your inquiry has been submitted. We will get back to you shortly.',
            'id' => $inquiry->id,
        ], 201);
    }

    /**
     * Submit a quick inquiry using the authenticated user's details.
     */
    public function quickInquiry(QuickInquiryRequest $request, Property $property): JsonResponse
    {
        $inquiry = $this->propertyService->submitQuickInquiry(
            $property,
            $request->user(),
            $request->validated('message'),
        );

        return response()->json([
            'message' => 'Your interest has been sent to the '.($property->store?->isPaupahan() ? 'landlord' : 'agent').'.',
            'id' => $inquiry->id,
        ], 201);
    }

    /**
     * List upcoming open house events for a property.
     */
    public function openHouses(Property $property): JsonResponse
    {
        return response()->json(
            $this->propertyService->upcomingOpenHouses($property)
        );
    }
}
