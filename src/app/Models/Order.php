<?php

namespace App\Models;

use App\OrderPaymentMethod;
use App\OrderPaymentStatus;
use App\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
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
 * @property ?string $payment_intent_id
 * @property ?string $payment_client_key
 * @property ?string $payment_method
 * @property ?string $payment_status
 * @property ?Carbon $paid_at
 * @property ?Carbon $cancelled_at
 *
 * @see /skills/commission-calculation.md
 * @see /skills/order-processing.md
 */
class Order extends LunarOrder
{
    /**
     * Point to our custom OrderFactory.
     */
    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    /**
     * Override Lunar's logAll() to exclude Price cast fields that are not
     * serialisable by Spatie's dirty-attribute comparator.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('lunar')
            ->logOnly(['status', 'payment_status', 'store_id', 'user_id'])
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
            'payment_method' => OrderPaymentMethod::class,
            'payment_status' => OrderPaymentStatus::class,
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
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
     * Return the payout line linking this order to a payout.
     */
    public function payoutLine(): HasOne
    {
        return $this->hasOne(PayoutLine::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function latestShipment(): HasOne
    {
        return $this->hasOne(Shipment::class)->latestOfMany();
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
