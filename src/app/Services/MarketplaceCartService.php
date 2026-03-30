<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Lunar\Models\Cart;

class MarketplaceCartService
{
    /**
     * @return Collection<int, array{
     *     store: Store,
     *     lines: Collection<int, mixed>,
     *     sub_total: int,
     *     total: int,
     *     quantity: int
     * }>
     */
    public function groupCartByStore(Cart $cart): Collection
    {
        $cart->loadMissing('lines');
        $cart->lines->loadMissing('purchasable.product');

        $groupedLines = $cart->lines
            ->filter(fn ($line): bool => $this->resolveLineStoreId($line) !== null)
            ->groupBy(fn ($line): int => $this->resolveLineStoreId($line));

        $stores = Store::query()
            ->whereIn('id', $groupedLines->keys()->all())
            ->get()
            ->keyBy('id');

        return $groupedLines
            ->map(function (Collection $lines, int|string $storeId) use ($stores): ?array {
                $store = $stores->get((int) $storeId);

                if (! $store) {
                    return null;
                }

                return [
                    'store' => $store,
                    'lines' => $lines->values(),
                    'sub_total' => $lines->sum(fn ($line): int => (int) ($line->subTotal?->value ?? 0)),
                    'total' => $lines->sum(fn ($line): int => (int) ($line->total?->value ?? 0)),
                    'quantity' => $lines->sum(fn ($line): int => (int) $line->quantity),
                ];
            })
            ->filter()
            ->values();
    }

    public function hasMultipleStores(Cart $cart): bool
    {
        return $this->groupCartByStore($cart)->count() > 1;
    }

    public function resolveSingleStore(Cart $cart): Store
    {
        $groups = $this->groupCartByStore($cart);

        if ($groups->count() !== 1) {
            throw ValidationException::withMessages([
                'cart' => ['Your cart now contains items from multiple stores. Complete checkout from the grouped marketplace flow instead.'],
            ]);
        }

        return $groups->first()['store'];
    }

    public function resolveLineStoreId(mixed $line): ?int
    {
        $storeId = $line?->meta['store_id'] ?? null;

        if ($storeId) {
            return (int) $storeId;
        }

        $attributeStoreId = $line?->purchasable?->product?->attribute_data?->get('store_id')?->getValue();

        return $attributeStoreId ? (int) $attributeStoreId : null;
    }
}
