<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $store_id
 * @property string $name
 * @property ?string $description
 * @property int $price Stored in centavos
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Store $store
 */
class MovingAddOn extends Model
{
    /** @use HasFactory<\Database\Factories\MovingAddOnFactory> */
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'description',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(MovingBooking::class, 'moving_booking_add_on')
            ->withPivot('price')
            ->withTimestamps();
    }
}
