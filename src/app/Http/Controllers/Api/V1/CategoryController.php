<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;

class CategoryController extends Controller
{
    /**
     * Return all collections from the "Marketplace Categories" group.
     *
     * GET /api/v1/categories
     *
     * Response shape:
     * [
     *   { "id": 14, "name": "Electronics", "slug": "electronics" },
     *   ...
     * ]
     */
    public function index(): JsonResponse
    {
        $group = CollectionGroup::where('handle', 'marketplace-categories')->first();

        if (! $group) {
            return response()->json([]);
        }

        $categories = Collection::where('collection_group_id', $group->id)
            ->get()
            ->map(fn (Collection $c): array => [
                'id' => $c->id,
                'name' => $c->translateAttribute('name'),
                'slug' => Str::slug($c->translateAttribute('name')),
            ])
            ->values();

        return response()->json($categories);
    }
}
