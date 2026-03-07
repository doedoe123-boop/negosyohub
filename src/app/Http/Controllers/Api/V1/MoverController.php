<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\IndustrySector;
use App\Models\Store;
use App\Services\MovingBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MoverController extends Controller
{
    public function __construct(private MovingBookingService $service) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $movers = $this->service->browseMovers(
            city: $validated['city'] ?? null,
            province: $validated['province'] ?? null,
            perPage: (int) ($validated['per_page'] ?? 15),
        );

        return response()->json($movers);
    }

    public function show(Store $store): JsonResponse
    {
        abort_if($store->sector !== IndustrySector::LipatBahay, 404);

        $store->load(['movingAddOns' => fn ($q) => $q->where('is_active', true)->orderBy('name')]);

        return response()->json($store);
    }
}
