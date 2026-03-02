<?php

namespace App\Models;

use App\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Base\Casts\Price;
use Lunar\Models\Order as LunarOrder;
use Spatie\Activitylog\LogOptions;

/**
 * Extends Lunar's Order model with marketplace-specific fields.
 *
 * @property ?int $store_id
 * @property int $commission_amount
 * @property int $store_earning
 * @property int $platform_earning
 *
 * @see /skills/commission-calculation.md
 * @see /skills/order-processing.md
 */
class Order extends LunarOrder
{
    /**
     * Point to our custom OrderFactory.
     */
    protected static function newFactory(): \Database\Factories\OrderFactory
    {
        return \Database\Factories\OrderFactory::new();
    }

    /**
     * Override Lunar's logAll() to exclude Price cast fields that are not
     * serialisable by Spatie's dirty-attribute comparator.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('lunar')
            ->logOnly(['status', 'store_id', 'user_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Additional casts for marketplace columns.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'commission_amount' => Price::class,
            'store_earning' => Price::class,
            'platform_earning' => Price::class,
        ]);
    }

    // ── Relationships ──────────────────────────────────────────────────

    /**
     * Return the store relationship.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Return the customer who placed this order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    /**
     * Scope orders belonging to a specific store.
     */
    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Scope orders with pending status.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::Pending);
    }

    /**
     * Scope orders that are still active (not delivered/cancelled).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(
            fn (OrderStatus $s) => $s->value,
            OrderStatus::active()
        ));
    }

    /**
     * Scope delivered orders only.
     */
    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::Delivered);
    }
}
