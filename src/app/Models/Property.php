<?php

namespace App\Models;

use App\ListingType;
use App\PropertyStatus;
use App\PropertyType;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $store_id
 * @property string $title
 * @property string $slug
 * @property ?string $description
 * @property PropertyType $property_type
 * @property ListingType $listing_type
 * @property PropertyStatus $status
 * @property float $price
 * @property string $price_currency
 * @property ?string $price_period
 * @property ?int $bedrooms
 * @property ?int $bathrooms
 * @property ?int $garage_spaces
 * @property ?float $floor_area
 * @property ?float $lot_area
 * @property ?int $year_built
 * @property ?int $floors
 * @property ?string $address_line
 * @property ?string $barangay
 * @property string $city
 * @property ?string $province
 * @property ?string $zip_code
 * @property ?float $latitude
 * @property ?float $longitude
 * @property ?array $features
 * @property ?array $images
 * @property ?array $floor_plans
 * @property ?array $documents
 * @property ?array $nearby_places
 * @property ?array $direction_steps
 * @property ?array $house_rules
 * @property ?array $utility_inclusions
 * @property ?array $safety_features
 * @property ?string $video_url
 * @property ?string $virtual_tour_url
 * @property bool $is_featured
 * @property ?Carbon $published_at
 * @property int $views_count
 */
class Property extends Model implements HasMedia
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use Searchable;
    use SoftDeletes;

    protected $appends = [
        'average_rating',
        'review_count',
    ];

    /** @var list<string> */
    protected $fillable = [
        'store_id',
        'development_id',
        'unit_number',
        'unit_floor',
        'title',
        'slug',
        'description',
        'property_type',
        'listing_type',
        'status',
        'price',
        'price_currency',
        'price_period',
        'bedrooms',
        'bathrooms',
        'garage_spaces',
        'floor_area',
        'lot_area',
        'year_built',
        'floors',
        'address_line',
        'barangay',
        'city',
        'province',
        'zip_code',
        'latitude',
        'longitude',
        'features',
        'images',
        'floor_plans',
        'documents',
        'nearby_places',
        'direction_steps',
        'house_rules',
        'utility_inclusions',
        'safety_features',
        'video_url',
        'virtual_tour_url',
        'is_featured',
        'published_at',
        'views_count',
        'seo_title',
        'seo_description',
        'seo_keywords',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'property_type' => PropertyType::class,
            'listing_type' => ListingType::class,
            'status' => PropertyStatus::class,
            'price' => 'decimal:2',
            'floor_area' => 'decimal:2',
            'lot_area' => 'decimal:2',
            'features' => 'array',
            'floor_plans' => 'array',
            'documents' => 'array',
            'nearby_places' => 'array',
            'direction_steps' => 'array',
            'house_rules' => 'array',
            'utility_inclusions' => 'array',
            'safety_features' => 'array',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }

    // ── Accessors ─────────────────────────────────────────────────────

    /**
     * Return image URLs from the Spatie media library when loaded,
     * falling back to the legacy JSON column value.
     *
     * @return Attribute<list<string>, list<string>|string>
     */
    protected function images(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->relationLoaded('media')) {
                    $urls = $this->getMedia('images')
                        ->map(fn ($m) => $m->getFullUrl())
                        ->values()
                        ->toArray();

                    if (! empty($urls)) {
                        return $urls;
                    }
                }

                $paths = json_decode($value ?? '[]', true) ?? [];

                return array_map(function ($path) {
                    if (str_starts_with($path, 'http')) {
                        return $path;
                    }

                    return Storage::disk('public')->url($path);
                }, $paths);
            },
            set: fn ($value) => is_array($value) ? json_encode($value) : $value,
        );
    }

    // ── Media ──────────────────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useFallbackUrl('/images/placeholder-property.jpg');

        $this->addMediaCollection('floor_plans');
    }

    // ── Boot ───────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $property): void {
            if (empty($property->slug)) {
                $property->slug = Str::slug($property->title).'-'.Str::random(6);
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────────

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function development(): BelongsTo
    {
        return $this->belongsTo(Development::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(PropertyInquiry::class);
    }

    public function openHouses(): HasMany
    {
        return $this->hasMany(OpenHouse::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(PropertyAnalytic::class);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PropertyStatus::Active);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true)->active();
    }

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    /**
     * Get formatted price string.
     */
    public function formattedPrice(): string
    {
        $formatted = $this->price_currency.' '.number_format((float) $this->price, 2);

        if ($this->price_period && $this->listing_type !== ListingType::ForSale) {
            $formatted .= ' / '.$this->price_period;
        }

        return $formatted;
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
     * Get the average review rating for this property.
     */
    public function averageRating(): ?float
    {
        $avg = $this->testimonials()
            ->where('is_published', true)
            ->avg('rating');

        return $avg ? round((float) $avg, 1) : null;
    }

    public function getAverageRatingAttribute(): ?float
    {
        return $this->averageRating();
    }

    public function getReviewCountAttribute(): int
    {
        return $this->testimonials()->where('is_published', true)->count();
    }

    /**
     * Increment the views counter.
     */
    public function recordView(): void
    {
        $this->increment('views_count');
    }

    /**
     * Publish the listing.
     */
    public function publish(): void
    {
        $this->update([
            'status' => PropertyStatus::Active,
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === PropertyStatus::Active && $this->published_at !== null;
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'city' => $this->city,
            'province' => $this->province,
            'address_line' => $this->address_line,
            'listing_type' => $this->listing_type?->value,
            'property_type' => $this->property_type?->value,
        ];
    }
}
