<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Lunar\Models\Product;

/**
 * Read-only product browsing service for the customer storefront.
 *
 * Products are Lunar entities whose store association is stored inside
 * attribute_data as a Number FieldType (store_id -> value).
 */
class ProductService
{
    /**
     * Browse published products scoped to a specific approved store.
     *
     * @param  array{search?: string, per_page?: int}  $params
     */
    public function browseForStore(Store $store, array $params = []): LengthAwarePaginator
    {
        return $this->baseQuery($params)
            ->whereJsonContains('attribute_data->store_id->value', $store->id)
            ->paginate($params['per_page'] ?? 16)
            ->through(fn (Product $product) => $this->formatProduct($product));
    }

    /**
     * Browse all published products across all stores.
     *
     * @param  array{search?: string, per_page?: int}  $params
     */
    public function browse(array $params = []): LengthAwarePaginator
    {
        return $this->baseQuery($params)
            ->paginate($params['per_page'] ?? 16)
            ->through(fn (Product $product) => $this->formatProduct($product));
    }

    /**
     * Return the N most recently published products for homepage previews.
     *
     * @return array<int, array<string, mixed>>
     */
    public function featured(int $limit = 6): array
    {
        return $this->baseQuery()
            ->limit($limit)
            ->get()
            ->map(fn (Product $product) => $this->formatProduct($product))
            ->values()
            ->toArray();
    }

    /**
     * Find a single published product by ID with variants, or abort 404.
     *
     * @return array<string, mixed>
     */
    public function findOrFail(int $id): array
    {
        $product = Product::query()
            ->with(['variants.prices.currency', 'media'])
            ->where('status', 'published')
            ->findOrFail($id);

        return $this->formatProduct($product, detailed: true);
    }

    /**
     * Build the shared base query for published products with eager loads.
     *
     * @param  array{search?: string}  $params
     */
    private function baseQuery(array $params = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = Product::query()
            ->with(['variants.prices.currency', 'media'])
            ->where('status', 'published')
            ->latest();

        if (! empty($params['search'])) {
            $search = $params['search'];
            // Use driver-aware JSON extraction so the query works on both
            // PostgreSQL (production) and SQLite (test environment).
            if (DB::connection()->getDriverName() === 'sqlite') {
                $query->whereRaw("json_extract(attribute_data, '$.name.value') LIKE ?", ["%{$search}%"]);
            } else {
                $query->whereRaw("attribute_data->'name'->>'value' ILIKE ?", ["%{$search}%"]);
            }
        }

        return $query;
    }

    /**
     * Transform a Lunar Product into an API-friendly array.
     *
     * @return array<string, mixed>
     */
    public function formatProduct(Product $product, bool $detailed = false): array
    {
        $variants = $product->relationLoaded('variants')
            ? $product->variants
            : $product->variants()->with('prices.currency')->get();

        $firstVariant = $variants->first();
        $firstPrice = $firstVariant?->prices?->first();

        $data = [
            'id' => $product->id,
            'name' => $product->translateAttribute('name'),
            'description' => $product->translateAttribute('description'),
            'thumbnail' => $product->getFirstMediaUrl('images') ?: null,
            'images' => $product->getMedia('images')->map(fn ($m) => $m->getFullUrl())->values()->toArray(),
            'store_id' => $product->attribute_data->get('store_id')?->getValue(),
            'default_variant_id' => $firstVariant?->id,
            'price' => $firstPrice ? round($firstPrice->price->value / 100, 2) : null,
            'currency' => $firstPrice?->currency?->code,
        ];

        if ($detailed) {
            $data['variants'] = $variants->map(fn ($variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'stock' => $variant->stock,
                'price' => $variant->prices->first()
                    ? round($variant->prices->first()->price->value / 100, 2)
                    : null,
                'currency' => $variant->prices->first()?->currency?->code,
            ])->values()->toArray();

            $storeId = $data['store_id'];
            $store = $storeId ? Store::query()->find($storeId) : null;
            $data['store'] = $store ? [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'logo' => $store->logo,
                'sector' => $store->sector,
                'city' => $store->address['city'] ?? null,
            ] : null;
        }

        $avgRatingQuery = \App\Models\Review::query()
            ->where('reviewable_type', \Lunar\Models\Product::class)
            ->where('reviewable_id', $product->id)
            ->where('is_published', true);

        $avgRating = (clone $avgRatingQuery)->avg('rating');

        $data['average_rating'] = $avgRating ? round((float) $avgRating, 1) : null;
        $data['review_count'] = (clone $avgRatingQuery)->count();

        if ($detailed) {
            $data['reviews'] = (clone $avgRatingQuery)
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->reviewer_name,
                    'rating' => $r->rating,
                    'content' => $r->content,
                    'verified' => $r->is_verified_purchase,
                    'date' => $r->created_at ? $r->created_at->diffForHumans() : 'Recently',
                ])
                ->toArray();
        }

        return $data;
    }
}
