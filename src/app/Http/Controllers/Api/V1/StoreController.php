<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\PropertyService;
use App\Services\ReviewEligibilityService;
use App\Services\StoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Thin HTTP handler for read-only store browsing.
 *
 * Filtering, pagination, and availability checks live in StoreService.
 * Store registration, approval, and suspension are handled exclusively
 * through the Blade/Livewire seller portal (routes/web.php).
 *
 * @see /skills/store-management.md
 */
class StoreController extends Controller
{
    public function __construct(
        private StoreService $storeService,
        private PropertyService $propertyService,
        private ReviewEligibilityService $reviewEligibilityService,
    ) {}

    /**
     * List approved stores, with optional sector/city/search/collection_id filtering.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->storeService->browseApproved($request->only(['sector', 'city', 'search', 'per_page', 'collection_id']))
        );
    }

    /**
     * Show a single approved store.
     */
    public function show(Store $store): JsonResponse
    {
        $store = $this->storeService->findApprovedOrFail($store);
        $payload = $store->toArray();

        if ($requestUser = request()->user('sanctum')) {
            $payload['review_eligibility'] = $this->reviewEligibilityService->forStore($requestUser, $store);
        }

        return response()->json($payload);
    }

    /**
     * List published properties for a real-estate store.
     *
     * Resolves the store by slug. Returns 404 if not approved.
     */
    public function storeProperties(Store $store, Request $request): JsonResponse
    {
        abort_if(! $store->isApproved(), 404);

        return response()->json(
            $this->propertyService->browseForStore(
                $store,
                $request->only(['search', 'type', 'listing_type', 'min_price', 'max_price', 'bedrooms', 'per_page'])
            )
        );
    }
}
