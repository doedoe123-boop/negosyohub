<?php

namespace App\Http\Controllers\Api\V1;

use App\FeaturedType;
use App\Http\Controllers\Controller;
use App\Models\FeaturedListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public featured listing endpoints for the customer storefront.
 *
 * Returns active featured stores, products, or services with their
 * related entities eagerly loaded.
 */
class FeaturedListingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FeaturedListing::query()
            ->active()
            ->with('featurable')
            ->orderByDesc('priority')
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $type = FeaturedType::tryFrom($request->input('type'));
            if ($type) {
                $query->ofType($type);
            }
        }

        $listings = $query->limit((int) $request->input('limit', 12))->get();

        // Transform to include the featured entity data
        $data = $listings->map(function (FeaturedListing $listing): array {
            return [
                'id' => $listing->id,
                'featured_type' => $listing->featured_type->value,
                'priority' => $listing->priority,
                'starts_at' => $listing->starts_at,
                'ends_at' => $listing->ends_at,
                'item' => $listing->featurable,
            ];
        });

        return response()->json($data);
    }
}
