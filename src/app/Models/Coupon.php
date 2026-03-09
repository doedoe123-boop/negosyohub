<?php

namespace App\Models;

use App\AdStatus;
use App\CouponScope;
use App\CouponType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $code
 * @property ?string $description
 * @property CouponType $type
 * @property CouponScope $scope
 * @property AdStatus $status
 * @property int $value
 * @property ?int $min_order_cents
 * @property ?int $max_discount_cents
 * @property ?int $max_uses
 * @property int $times_used
 * @property ?int $store_id
 * @property ?string $sector
 * @property ?\Illuminate\Support\Carbon $starts_at
 * @property ?\Illuminate\Support\Carbon $ends_at
 * @property ?int $campaign_id
 * @property ?int $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Coupon extends Model
{
    /** @use HasFactory<\Database\Factories\CouponFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'description',
        'type',
        'scope',
        'status',
        'value',
        'min_order_cents',
        'max_discount_cents',
        'max_uses',
        'times_used',
        'store_id',
        'sector',
        'starts_at',
        'ends_at',
        'campaign_id',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'scope' => CouponScope::class,
            'status' => AdStatus::class,
            'value' => 'integer',
            'min_order_cents' => 'integer',
            'max_discount_cents' => 'integer',
            'max_uses' => 'integer',
            'times_used' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AdStatus::Active)
            ->where(function (Builder $q): void {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->where(function (Builder $q): void {
                $q->whereNull('max_uses')
                    ->orWhereColumn('times_used', '<', 'max_uses');
            });
    }

    public function isUsable(): bool
    {
        if ($this->status !== AdStatus::Active) {
            return false;
        }

        if ($this->ends_at !== null && $this->ends_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->times_used >= $this->max_uses) {
            return false;
        }

        return true;
    }
}
