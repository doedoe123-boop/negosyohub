<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\OrderStatus;
use Lunar\Models\Product;

class ReviewEligibilityService
{
    /**
     * @return array{can_submit: bool, reason: ?string, is_verified_purchase: bool}
     */
    public function forStore(?User $user, Store $store): array
    {
        if (! $user) {
            return [
                'can_submit' => false,
                'reason' => null,
                'is_verified_purchase' => false,
            ];
        }

        $hasDeliveredOrder = Order::query()
            ->where('user_id', $user->id)
            ->where('store_id', $store->id)
            ->where('status', OrderStatus::Delivered->value)
            ->exists();

        if (! $hasDeliveredOrder) {
            return [
                'can_submit' => false,
                'reason' => 'You can review this store after a delivered order from this seller.',
                'is_verified_purchase' => false,
            ];
        }

        return [
            'can_submit' => true,
            'reason' => null,
            'is_verified_purchase' => true,
        ];
    }

    /**
     * @return array{can_submit: bool, reason: ?string, is_verified_purchase: bool}
     */
    public function forProduct(?User $user, Product $product): array
    {
        if (! $user) {
            return [
                'can_submit' => false,
                'reason' => null,
                'is_verified_purchase' => false,
            ];
        }

        $storeId = (int) ($product->attribute_data->get('store_id')?->getValue() ?? 0);
        $qualifyingOrders = Order::query()
            ->where('user_id', $user->id)
            ->when($storeId > 0, fn ($query) => $query->where('store_id', $storeId))
            ->where('status', OrderStatus::Delivered->value)
            ->with(['lines.purchasable.product'])
            ->latest()
            ->get();

        $hasDeliveredOrder = $qualifyingOrders->contains(function (Order $order) use ($product): bool {
            return $order->lines->contains(function ($line) use ($product): bool {
                if ($line->type === 'shipping') {
                    return false;
                }

                return (int) ($line->purchasable?->product?->id ?? 0) === $product->id;
            });
        });

        if (! $hasDeliveredOrder) {
            return [
                'can_submit' => false,
                'reason' => 'You can review this product after a delivered order containing this item.',
                'is_verified_purchase' => false,
            ];
        }

        return [
            'can_submit' => true,
            'reason' => null,
            'is_verified_purchase' => true,
        ];
    }
}
