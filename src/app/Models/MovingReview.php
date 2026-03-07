<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $moving_booking_id
 * @property int $store_id
 * @property int $customer_user_id
 * @property int $rating 1-5
 * @property ?string $comment
 * @property bool $is_published
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read MovingBooking $booking
 * @property-read Store $store
 * @property-read User $customer
 */
class MovingReview extends Model
{
    /** @use HasFactory<\Database\Factories\MovingReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'moving_booking_id',
        'store_id',
        'customer_user_id',
        'rating',
        'comment',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(MovingBooking::class, 'moving_booking_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }
}
