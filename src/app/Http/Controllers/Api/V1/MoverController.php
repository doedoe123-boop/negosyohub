<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\SectorTemplate;
use App\Services\MovingBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MoverController extends Controller
{
    public function __construct(private MovingBookingService $service) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $movers = $this->service->browseMovers($validated);

        return response()->json($movers);
    }

    public function show(Store $store): JsonResponse
    {
        abort_if($store->template() !== SectorTemplate::Logistics, 404);

        $store->load(['movingAddOns' => fn ($q) => $q->where('is_active', true)->orderBy('name')]);

        return response()->json($store);
    }
}
