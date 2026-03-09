<?php

namespace App\Models;

use App\AdPlacement;
use App\AdStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $title
 * @property ?string $description
 * @property AdPlacement $placement
 * @property AdStatus $status
 * @property ?string $image_url
 * @property ?string $link_url
 * @property ?string $advertisable_type
 * @property ?int $advertisable_id
 * @property int $priority
 * @property int $cost_cents
 * @property ?\Illuminate\Support\Carbon $starts_at
 * @property ?\Illuminate\Support\Carbon $ends_at
 * @property ?int $campaign_id
 * @property ?int $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Advertisement extends Model
{
    /** @use HasFactory<\Database\Factories\AdvertisementFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'placement',
        'status',
        'image_url',
        'link_url',
        'advertisable_type',
        'advertisable_id',
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
            'placement' => AdPlacement::class,
            'status' => AdStatus::class,
            'priority' => 'integer',
            'cost_cents' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function advertisable(): MorphTo
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

    public function scopeForPlacement(Builder $query, AdPlacement $placement): Builder
    {
        return $query->where('placement', $placement);
    }
}
