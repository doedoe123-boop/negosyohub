<?php

namespace App\Models;

use App\AdStatus;
use App\FeaturedType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property FeaturedType $featured_type
 * @property string $featurable_type
 * @property int $featurable_id
 * @property AdStatus $status
 * @property int $priority
 * @property int $cost_cents
 * @property ?\Illuminate\Support\Carbon $starts_at
 * @property ?\Illuminate\Support\Carbon $ends_at
 * @property ?int $campaign_id
 * @property ?int $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class FeaturedListing extends Model
{
    /** @use HasFactory<\Database\Factories\FeaturedListingFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'featured_type',
        'featurable_type',
        'featurable_id',
        'status',
        'priority',
        'cost_cents',
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
            'featured_type' => FeaturedType::class,
            'status' => AdStatus::class,
            'priority' => 'integer',
            'cost_cents' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function featurable(): MorphTo
    {
        return $this->morphTo();
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
            });
    }

    public function scopeOfType(Builder $query, FeaturedType $type): Builder
    {
        return $query->where('featured_type', $type);
    }
}
