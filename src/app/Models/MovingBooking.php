<?php

namespace App\Models;

use App\MovingBookingStatus;
use App\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $store_id
 * @property int $customer_user_id
 * @property ?int $rental_agreement_id
 * @property MovingBookingStatus $status
 * @property string $pickup_address
 * @property string $delivery_address
 * @property string $pickup_city
 * @property string $delivery_city
 * @property \Illuminate\Support\Carbon $scheduled_at
 * @property string $contact_name
 * @property string $contact_phone
 * @property ?string $notes
 * @property int $base_price Centavos
 * @property int $add_ons_total Centavos
 * @property int $total_price Centavos
 * @property PaymentStatus $payment_status
 * @property ?string $paymongo_payment_intent_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property-read Store $store
 * @property-read User $customer
 * @property-read ?RentalAgreement $rentalAgreement
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MovingAddOn> $addOns
 * @property-read ?MovingReview $review
 */
class MovingBooking extends Model
{
    /** @use HasFactory<\Database\Factories\MovingBookingFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'customer_user_id',
        'rental_agreement_id',
        'status',
        'pickup_address',
        'delivery_address',
        'pickup_city',
        'delivery_city',
        'scheduled_at',
        'contact_name',
        'contact_phone',
        'notes',
        'base_price',
        'add_ons_total',
        'total_price',
        'payment_status',
        'paymongo_payment_intent_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => MovingBookingStatus::class,
            'payment_status' => PaymentStatus::class,
            'scheduled_at' => 'datetime',
            'base_price' => 'integer',
            'add_ons_total' => 'integer',
            'total_price' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function rentalAgreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class);
    }

    public function addOns(): BelongsToMany
    {
        return $this->belongsToMany(MovingAddOn::class, 'moving_booking_add_on')
            ->withPivot('price')
            ->withTimestamps();
    }

    public function review(): HasOne
    {
        return $this->hasOne(MovingReview::class);
    }
}
