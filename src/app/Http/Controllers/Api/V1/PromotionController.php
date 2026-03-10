<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public promotion endpoints for the customer storefront.
 *
 * Returns only active, non-expired promotions for display as banners,
 * badges, or sale sections.
 */
class PromotionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $promotions = Promotion::query()
            ->active()
            ->orderByDesc('starts_at')
            ->limit((int) $request->input('limit', 10))
            ->get([
                'id', 'name', 'description', 'type', 'discount_percentage',
                'discount_amount_cents', 'starts_at', 'ends_at',
            ]);

        return response()->json($promotions);
    }
}
