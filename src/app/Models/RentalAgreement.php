<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $property_id
 * @property int $store_id
 * @property ?int $tenant_user_id
 * @property string $tenant_name
 * @property string $tenant_email
 * @property ?string $tenant_phone
 * @property int $monthly_rent Stored in cents
 * @property ?int $security_deposit Stored in cents
 * @property \Illuminate\Support\Carbon $move_in_date
 * @property ?int $lease_term_months
 * @property ?string $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property-read Property $property
 * @property-read Store $store
 * @property-read ?User $tenantUser
 */
class RentalAgreement extends Model
{
    /** @use HasFactory<\Database\Factories\RentalAgreementFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'property_id',
        'store_id',
        'tenant_user_id',
        'tenant_name',
        'tenant_email',
        'tenant_phone',
        'monthly_rent',
        'security_deposit',
        'move_in_date',
        'lease_term_months',
        'notes',
        'status',
        'tenant_questions',
        'landlord_response',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'move_in_date' => 'date',
            'monthly_rent' => 'integer',
            'security_deposit' => 'integer',
            'lease_term_months' => 'integer',
            'signed_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }
}
