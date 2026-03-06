<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Property;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Lightweight stats endpoint for the public storefront homepage.
 *
 * Returns aggregate counts that power the hero social-proof bar,
 * the trust strip, and the footer. Cached for 5 minutes.
 */
class HomepageStatsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $stats = Cache::remember('homepage_stats', now()->addMinutes(5), function () {
            $storeCount    = Store::approved()->count();
            $productCount  = Product::count();
            $propertyCount = Property::whereNotNull('published_at')->count();

            // Average rating across all published reviews
            $avgRating   = null;
            $reviewCount = 0;
            try {
                $avgRating = DB::table('reviews')
                    ->where('is_published', true)
                    ->avg('rating');

                $reviewCount = DB::table('reviews')
                    ->where('is_published', true)
                    ->count();

                $avgRating = $avgRating ? round((float) $avgRating, 1) : null;
            } catch (\Throwable) {
                // Reviews table may not exist yet — graceful fallback
            }

            return [
                'stores'          => $storeCount,
                'products'        => $productCount,
                'properties'      => $propertyCount,
                'average_rating'  => $avgRating,
                'total_reviews'   => $reviewCount,
            ];
        });

        return response()->json($stats);
    }
}
