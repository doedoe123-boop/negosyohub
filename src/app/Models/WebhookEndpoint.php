<?php

namespace App\Models;

use Database\Factories\WebhookEndpointFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookEndpoint extends Model
{
    /** @use HasFactory<WebhookEndpointFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'last_delivered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'last_delivered_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * @return array<string, string>
     */
    public static function eventOptions(): array
    {
        return [
            'order.created' => 'Order Created',
            'order.updated' => 'Order Updated',
            'order.delivered' => 'Order Delivered',
            'payment.paid' => 'Payment Paid',
            'payment.failed' => 'Payment Failed',
            'shipment.created' => 'Shipment Created',
            'shipment.updated' => 'Shipment Updated',
            'store.approved' => 'Store Approved',
        ];
    }
}
