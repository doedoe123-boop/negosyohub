<?php

namespace App\Http\Controllers\Api\V1;

use App\AdPlacement;
use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public advertisement endpoints for the customer storefront.
 *
 * Returns only active, non-expired advertisements filtered by placement.
 */
class AdvertisementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Advertisement::query()
            ->active()
            ->orderByDesc('priority')
            ->orderByDesc('created_at');

        if ($request->filled('placement')) {
            $placement = AdPlacement::tryFrom($request->input('placement'));
            if ($placement) {
                $query->forPlacement($placement);
            }
        }

        $ads = $query->limit((int) $request->input('limit', 10))
            ->get([
                'id', 'title', 'description', 'placement', 'image_url',
                'link_url', 'priority', 'starts_at', 'ends_at',
            ]);

        return response()->json($ads);
    }
}
