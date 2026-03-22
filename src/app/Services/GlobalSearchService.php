<?php

namespace App\Services;

use App\Models\Development;
use App\Models\Property;
use App\Models\Sector;
use App\Models\Store;
use App\PropertyStatus;
use App\StoreStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lunar\Models\Product;

/**
 * Unified global search across all sectors: stores, products, and properties.
 *
 * Used by the homepage universal search and the navbar search overlay.
 * Returns top results from each category in a single response.
 */
class GlobalSearchService
{
    public function __construct(
        private ProductService $productService,
    ) {}

    /**
     * Perform a cross-sector search and return grouped results.
     *
     * @param  array{q?: string, sector?: string, per_section?: int}  $params
     * @return array{query: string, stores: array, products: array, properties: array, developments: array}
     */
    public function search(array $params = []): array
    {
        $query = trim($params['q'] ?? '');
        $sector = $params['sector'] ?? null;
        $perSection = min((int) ($params['per_section'] ?? 5), 20);

        if ($query === '') {
            return [
                'query' => '',
                'stores' => [],
                'products' => [],
                'properties' => [],
                'developments' => [],
            ];
        }

        $result = [
            'query' => $query,
            'stores' => [],
            'products' => [],
            'properties' => [],
            'developments' => [],
        ];

        // ── Stores ───────────────────────────────────────────────────
        if (! $sector || $sector === 'all' || $this->sectorSearches($sector, 'stores')) {
            $result['stores'] = $this->searchStores($query, $sector, $perSection);
        }

        // ── Products (e-commerce / service templates) ────────────────
        if (! $sector || $sector === 'all' || $this->sectorSearches($sector, 'products')) {
            $result['products'] = $this->searchProducts($query, $perSection);
        }

        // ── Properties (real estate / rental templates) ──────────────
        if (! $sector || $sector === 'all' || $this->sectorSearches($sector, 'properties')) {
            $result['properties'] = $this->searchProperties($query, $perSection);
            $result['developments'] = $this->searchDevelopments($query, $perSection);
        }

        return $result;
    }

    /**
     * Search approved stores by name or description.
     *
     * @return list<array{id: int, name: string, slug: string, logo_url: ?string, sector: ?string, city: ?string, description: ?string}>
     */
    private function searchStores(string $query, ?string $sector, int $limit): array
    {
        if ($this->usesScout(Store::class)) {
            return Store::search($query)
                ->take($limit)
                ->get()
                ->filter(fn (Store $store): bool => $store->status === StoreStatus::Approved)
                ->when($sector && $sector !== 'all', function ($stores) use ($sector) {
                    return $stores->filter(fn (Store $store): bool => $store->sector === $sector);
                })
                ->map(fn (Store $store) => [
                    'id' => $store->id,
                    'name' => $store->name,
                    'slug' => $store->slug,
                    'logo_url' => $store->logo_url,
                    'sector' => $store->sector,
                    'city' => $store->address['city'] ?? null,
                    'description' => $store->description ? Str::limit($store->description, 100) : null,
                ])
                ->values()
                ->toArray();
        }

        $builder = Store::query()
            ->where('status', StoreStatus::Approved);

        // Sector filtering by slug
        if ($sector && $sector !== 'all') {
            // Look up the sector — it may map to multiple slugs via template grouping
            $sectorRecord = Sector::query()->where('slug', $sector)->first();

            if ($sectorRecord?->template) {
                // Find all sector slugs sharing this template
                $slugs = Sector::query()
                    ->where('template', $sectorRecord->template)
                    ->pluck('slug')
                    ->toArray();
                $builder->whereIn('sector', $slugs);
            } else {
                $builder->where('sector', $sector);
            }
        }

        // ILIKE for PostgreSQL (case-insensitive), LIKE for SQLite (already case-insensitive)
        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        $builder->where(function ($q) use ($query, $like) {
            $q->where('name', $like, "%{$query}%")
                ->orWhere('description', $like, "%{$query}%");
        });

        return $builder
            ->limit($limit)
            ->get()
            ->map(fn (Store $store) => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'logo_url' => $store->logo_url,
                'sector' => $store->sector,
                'city' => $store->address['city'] ?? null,
                'description' => $store->description ? Str::limit($store->description, 100) : null,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Search published products by name.
     *
     * @return list<array{id: int, name: ?string, thumbnail: ?string, price: ?float, currency: ?string, store_id: ?int}>
     */
    private function searchProducts(string $query, int $limit): array
    {
        $builder = Product::query()
            ->with(['variants.prices.currency', 'media'])
            ->where('status', 'published');

        // Driver-aware JSON search (PostgreSQL vs SQLite for testing)
        if (DB::connection()->getDriverName() === 'sqlite') {
            $builder->whereRaw("json_extract(attribute_data, '$.name.value') LIKE ?", ["%{$query}%"]);
        } else {
            $builder->whereRaw("attribute_data->'name'->>'value' ILIKE ?", ["%{$query}%"]);
        }

        return $builder
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Product $product) => $this->productService->formatProduct($product))
            ->values()
            ->toArray();
    }

    /**
     * Search active published properties by title, city, or address.
     *
     * @return list<array{id: int, title: string, slug: string, city: ?string, province: ?string, listing_type: ?string, property_type: ?string, price: ?float, price_currency: ?string, price_period: ?string, bedrooms: ?int, floor_area: ?float, images: array}>
     */
    private function searchProperties(string $query, int $limit): array
    {
        if ($this->usesScout(Property::class)) {
            return Property::search($query)
                ->take($limit)
                ->get()
                ->filter(fn (Property $property): bool => $property->status === PropertyStatus::Active && $property->published_at !== null)
                ->map(fn (Property $property) => [
                    'id' => $property->id,
                    'title' => $property->title,
                    'slug' => $property->slug,
                    'city' => $property->city,
                    'province' => $property->province,
                    'listing_type' => $property->listing_type,
                    'property_type' => $property->property_type,
                    'price' => $property->price ? (float) $property->price : null,
                    'price_currency' => $property->price_currency ?? 'PHP',
                    'price_period' => $property->price_period,
                    'bedrooms' => $property->bedrooms,
                    'floor_area' => $property->floor_area ? (float) $property->floor_area : null,
                    'images' => $property->images ?? [],
                ])
                ->values()
                ->toArray();
        }

        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        return Property::query()
            ->where('status', PropertyStatus::Active)
            ->whereNotNull('published_at')
            ->where(function ($q) use ($query, $like) {
                $q->where('title', $like, "%{$query}%")
                    ->orWhere('city', $like, "%{$query}%")
                    ->orWhere('address_line', $like, "%{$query}%");
            })
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->map(fn (Property $property) => [
                'id' => $property->id,
                'title' => $property->title,
                'slug' => $property->slug,
                'city' => $property->city,
                'province' => $property->province,
                'listing_type' => $property->listing_type,
                'property_type' => $property->property_type,
                'price' => $property->price ? (float) $property->price : null,
                'price_currency' => $property->price_currency ?? 'PHP',
                'price_period' => $property->price_period,
                'bedrooms' => $property->bedrooms,
                'floor_area' => $property->floor_area ? (float) $property->floor_area : null,
                'images' => $property->images ?? [],
            ])
            ->values()
            ->toArray();
    }

    /**
     * @return list<array{id: int, name: string, slug: string, city: ?string, province: ?string, developer_name: ?string}>
     */
    private function searchDevelopments(string $query, int $limit): array
    {
        if ($this->usesScout(Development::class)) {
            return Development::search($query)
                ->take($limit)
                ->get()
                ->filter(fn (Development $development): bool => $development->status === 'active' && $development->published_at !== null)
                ->map(fn (Development $development) => [
                    'id' => $development->id,
                    'name' => $development->name,
                    'slug' => $development->slug,
                    'city' => $development->city,
                    'province' => $development->province,
                    'developer_name' => $development->developer_name,
                ])
                ->values()
                ->toArray();
        }

        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        return Development::query()
            ->active()
            ->whereNotNull('published_at')
            ->where(function ($queryBuilder) use ($query, $like): void {
                $queryBuilder->where('name', $like, "%{$query}%")
                    ->orWhere('developer_name', $like, "%{$query}%")
                    ->orWhere('city', $like, "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->map(fn (Development $development) => [
                'id' => $development->id,
                'name' => $development->name,
                'slug' => $development->slug,
                'city' => $development->city,
                'province' => $development->province,
                'developer_name' => $development->developer_name,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Check if a given sector slug's template includes a search category.
     */
    private function sectorSearches(string $sectorSlug, string $category): bool
    {
        $sector = Sector::query()->where('slug', $sectorSlug)->first();

        return $sector?->template
            && in_array($category, $sector->template->searchCategories(), true);
    }

    private function usesScout(string $modelClass): bool
    {
        return config('scout.driver') !== 'null' && method_exists($modelClass, 'search');
    }
}
