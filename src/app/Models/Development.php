<?php

namespace App\Models;

use Database\Factories\DevelopmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A real estate development (building, subdivision, township)
 * that groups multiple property listings under one project.
 *
 * @property int $id
 * @property int $store_id
 * @property string $name
 * @property string $slug
 * @property ?string $description
 * @property ?string $developer_name
 * @property string $development_type
 * @property string $status
 * @property ?string $address_line
 * @property ?string $barangay
 * @property string $city
 * @property ?string $province
 * @property ?string $zip_code
 * @property ?float $latitude
 * @property ?float $longitude
 * @property ?int $total_units
 * @property ?int $available_units
 * @property ?int $floors
 * @property ?int $year_built
 * @property ?float $price_range_min
 * @property ?float $price_range_max
 * @property ?array $amenities
 * @property ?array $images
 * @property ?string $logo
 * @property ?string $website_url
 * @property ?string $video_url
 * @property bool $is_featured
 * @property ?Carbon $published_at
 */
class Development extends Model implements HasMedia
{
    /** @use HasFactory<DevelopmentFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use Searchable;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'developer_name',
        'development_type',
        'status',
        'address_line',
        'barangay',
        'city',
        'province',
        'zip_code',
        'latitude',
        'longitude',
        'total_units',
        'available_units',
        'floors',
        'year_built',
        'price_range_min',
        'price_range_max',
        'amenities',
        'images',
        'logo',
        'website_url',
        'video_url',
        'is_featured',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_range_min' => 'decimal:2',
            'price_range_max' => 'decimal:2',
            'amenities' => 'array',
            'images' => 'array',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    // ── Media ──────────────────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile();

        $this->addMediaCollection('images');
    }

    // ── Boot ───────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $development): void {
            if (empty($development->slug)) {
                $development->slug = Str::slug($development->name).'-'.Str::random(6);
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────────

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    /**
     * Get formatted price range string.
     */
    public function priceRange(): string
    {
        if (! $this->price_range_min && ! $this->price_range_max) {
            return 'Price on request';
        }

        $min = $this->price_range_min ? '₱'.number_format((float) $this->price_range_min, 0) : '';
        $max = $this->price_range_max ? '₱'.number_format((float) $this->price_range_max, 0) : '';

        if ($min && $max) {
            return "{$min} – {$max}";
        }

        return $min ?: "Up to {$max}";
    }

    /**
     * Get the full location string.
     */
    public function fullLocation(): string
    {
        return collect([
            $this->address_line,
            $this->barangay,
            $this->city,
            $this->province,
        ])->filter()->implode(', ');
    }

    /**
     * Sync the available units count from actual property listings.
     */
    public function syncAvailableUnits(): void
    {
        $this->update([
            'available_units' => $this->properties()
                ->whereIn('status', ['active', 'draft'])
                ->count(),
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === 'active' && $this->published_at !== null;
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'developer_name' => $this->developer_name,
            'city' => $this->city,
            'province' => $this->province,
            'development_type' => $this->development_type,
        ];
    }
}
