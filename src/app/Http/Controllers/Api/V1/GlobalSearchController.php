<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Unified global search endpoint.
 *
 * Returns grouped results from stores, products, and properties
 * in a single response based on the query and optional sector filter.
 *
 * Used by the homepage UniversalSearch, the Navbar search overlay,
 * and any future search pages.
 */
class GlobalSearchController extends Controller
{
    public function __construct(
        private GlobalSearchService $searchService
    ) {}

    /**
     * GET /api/v1/search?q=...&sector=...&per_section=5
     *
     * @queryParam  q           string  Required. The search term.
     * @queryParam  sector      string  Optional. Filter by sector: all, ecommerce, real_estate, services.
     * @queryParam  per_section int     Optional. Max results per category (default 5, max 20).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validSectors = Sector::active()->pluck('slug')->prepend('all')->implode(',');

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:200'],
            'sector' => ['nullable', 'string', 'in:'.$validSectors],
            'per_section' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        return response()->json(
            $this->searchService->search($validated)
        );
    }
}
