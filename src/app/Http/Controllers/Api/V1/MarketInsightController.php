<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Sector;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class MarketInsightController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $payload = Cache::remember('market_insights', now()->addMinutes(10), function (): array {
            $approvedStores = Store::approved()->get([
                'id',
                'sector',
                'address',
                'business_permit',
            ]);

            $sectorNames = Sector::query()
                ->pluck('name', 'slug');

            $cityCounts = $approvedStores
                ->map(function (Store $store): ?string {
                    $city = $store->address['city'] ?? null;

                    return is_string($city) && $city !== '' ? $city : null;
                })
                ->filter()
                ->countBy()
                ->sortDesc();

            $sectorCounts = $approvedStores
                ->map(fn (Store $store): string => (string) ($store->sector ?? 'unknown'))
                ->countBy()
                ->sortDesc();

            $approvedCount = $approvedStores->count();
            $withPermitCount = $approvedStores
                ->filter(fn (Store $store): bool => filled($store->business_permit))
                ->count();

            return [
                'stats' => [
                    'approved_suppliers' => $approvedCount,
                    'registered_users' => User::query()->count(),
                    'active_sectors' => Sector::active()->count(),
                    'cities_covered' => $cityCounts->count(),
                    'average_rating' => round((float) (Review::query()->published()->avg('rating') ?: 0), 1),
                    'published_reviews' => Review::query()->published()->count(),
                ],
                'top_sectors' => $sectorCounts
                    ->take(5)
                    ->map(function (int $total, string $slug) use ($sectorNames): array {
                        return [
                            'slug' => $slug,
                            'name' => $sectorNames[$slug] ?? ucwords(str_replace('_', ' ', $slug)),
                            'total' => $total,
                        ];
                    })
                    ->values()
                    ->all(),
                'top_cities' => $cityCounts
                    ->take(5)
                    ->map(function (int $total, string $city) use ($approvedCount): array {
                        return [
                            'city' => $city,
                            'total' => $total,
                            'share' => $approvedCount > 0 ? round(($total / $approvedCount) * 100, 1) : 0.0,
                        ];
                    })
                    ->values()
                    ->all(),
                'health' => [
                    'permit_compliance_rate' => $approvedCount > 0 ? round(($withPermitCount / $approvedCount) * 100) : 0,
                    'platform_status' => 'online',
                    'updated_every' => '24h',
                ],
                'generated_at' => now()->toIso8601String(),
            ];
        });

        return response()->json($payload);
    }
}
