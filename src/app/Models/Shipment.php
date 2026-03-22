<?php

namespace App\Models;

use App\DeliveryStatus;
use App\ShipmentProvider;
use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'provider',
        'external_reference',
        'delivery_status',
        'driver_name',
        'driver_contact',
        'vehicle_type',
        'tracking_url',
        'pickup_address',
        'dropoff_address',
        'booking_payload',
        'provider_response',
        'booked_at',
        'picked_up_at',
        'delivered_at',
        'failed_at',
        'cancelled_at',
        'last_synced_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => ShipmentProvider::class,
            'delivery_status' => DeliveryStatus::class,
            'booking_payload' => 'array',
            'provider_response' => 'array',
            'booked_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
