<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Store;
use App\PropertyStatus;
use App\StoreStatus;
use Illuminate\Support\Facades\DB;
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
     * @return array{query: string, stores: array, products: array, properties: array}
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
            ];
        }

        $result = [
            'query' => $query,
            'stores' => [],
            'products' => [],
            'properties' => [],
        ];

        // ── Stores ───────────────────────────────────────────────────
        if (! $sector || $sector === 'all' || $sector === 'ecommerce' || $sector === 'real_estate' || $sector === 'services') {
            $result['stores'] = $this->searchStores($query, $sector, $perSection);
        }

        // ── Products (e-commerce) ────────────────────────────────────
        if (! $sector || $sector === 'all' || $sector === 'ecommerce') {
            $result['products'] = $this->searchProducts($query, $perSection);
        }

        // ── Properties (real estate) ─────────────────────────────────
        if (! $sector || $sector === 'all' || $sector === 'real_estate') {
            $result['properties'] = $this->searchProperties($query, $perSection);
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
        $builder = Store::query()
            ->where('status', StoreStatus::Approved);

        // Sector filtering — map frontend sector labels to IndustrySector enum values
        if ($sector && $sector !== 'all') {
            if ($sector === 'services') {
                // "services" covers lipat_bahay and paupahan
                $builder->whereIn('sector', ['lipat_bahay', 'paupahan']);
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
                'sector' => $store->sector?->value,
                'city' => $store->address['city'] ?? null,
                'description' => $store->description ? \Illuminate\Support\Str::limit($store->description, 100) : null,
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
}
