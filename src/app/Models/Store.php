<?php

namespace App\Models;

use App\IndustrySector;
use App\StoreStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property ?string $login_token
 * @property ?string $description
 * @property ?string $logo
 * @property ?array $address
 * @property ?string $id_type
 * @property ?string $id_number
 * @property ?string $business_permit
 * @property ?array $compliance_documents
 * @property float $commission_rate
 * @property StoreStatus $status
 * @property ?IndustrySector $sector
 * @property ?string $agent_bio
 * @property ?string $agent_photo
 * @property ?array $agent_certifications
 * @property ?string $prc_license_number
 * @property ?array $agent_specializations
 * @property ?array $social_links
 * @property ?float $default_interest_rate
 * @property ?int $default_loan_term_years
 * @property ?float $default_down_payment_percent
 * @property ?string $tagline
 * @property ?string $phone
 * @property ?string $website
 * @property ?array $operating_hours
 * @property ?\Illuminate\Support\Carbon $suspended_at
 * @property ?string $suspension_reason
 * @property ?\Illuminate\Support\Carbon $setup_completed_at
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 *
 * @see /skills/store-management.md
 */
class Store extends Model
{
    /** @use HasFactory<\Database\Factories\StoreFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'sector', 'commission_rate', 'suspended_at', 'suspension_reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'login_token',
        'description',
        'logo',
        'address',
        'id_type',
        'id_number',
        'business_permit',
        'compliance_documents',
        'commission_rate',
        'status',
        'sector',
        'agent_bio',
        'agent_photo',
        'agent_certifications',
        'prc_license_number',
        'agent_specializations',
        'social_links',
        'default_interest_rate',
        'default_loan_term_years',
        'default_down_payment_percent',
        'tagline',
        'phone',
        'website',
        'operating_hours',
        'suspended_at',
        'suspension_reason',
        'setup_completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'address' => 'array',
            'id_number' => 'encrypted',
            'business_permit' => 'encrypted',
            'compliance_documents' => 'encrypted:array',
            'commission_rate' => 'decimal:2',
            'status' => StoreStatus::class,
            'sector' => IndustrySector::class,
            'agent_certifications' => 'array',
            'agent_specializations' => 'array',
            'social_links' => 'array',
            'default_interest_rate' => 'decimal:2',
            'default_down_payment_percent' => 'decimal:2',
            'operating_hours' => 'array',
            'suspended_at' => 'datetime',
            'setup_completed_at' => 'datetime',
        ];
    }

    /**
     * Return the owner relationship.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Return the staff members for this store.
     */
    public function staffMembers(): HasMany
    {
        return $this->hasMany(User::class, 'store_id');
    }

    /**
     * Return the orders relationship.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Return the payouts relationship.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    /**
     * Return the properties listed by this store (real estate).
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /**
     * Return all property inquiries for this store.
     */
    public function propertyInquiries(): HasMany
    {
        return $this->hasMany(PropertyInquiry::class);
    }

    /**
     * Return all developments for this store (real estate).
     */
    public function developments(): HasMany
    {
        return $this->hasMany(Development::class);
    }

    /**
     * Return all open house events for this store.
     */
    public function openHouses(): HasMany
    {
        return $this->hasMany(OpenHouse::class);
    }

    /**
     * Return all testimonials for this store.
     */
    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    /**
     * Return all support tickets for this store.
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * Return all e-commerce reviews (store + product) for this store.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the average rating from published reviews.
     */
    public function averageReviewRating(): float
    {
        return (float) $this->reviews()
            ->where('is_published', true)
            ->avg('rating') ?: 0;
    }

    /**
     * Return all property analytics for this store.
     */
    public function propertyAnalytics(): HasMany
    {
        return $this->hasMany(PropertyAnalytic::class);
    }

    /**
     * Scope to approved stores only.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', StoreStatus::Approved);
    }

    /**
     * Determine if the store is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === StoreStatus::Approved;
    }

    /**
     * Determine if the store is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === StoreStatus::Suspended;
    }

    /**
     * Determine whether the portal setup wizard has been completed.
     */
    public function isSetupComplete(): bool
    {
        return $this->setup_completed_at !== null;
    }

    /**
     * Return a public URL for the store logo, or null if unset.
     *
     * Handles both legacy http URLs and storage-relative paths.
     */
    public function logoUrl(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        if (str_starts_with($this->logo, 'http')) {
            return $this->logo;
        }

        return asset('storage/'.$this->logo);
    }

    /**
     * Determine if the store belongs to the real estate sector.
     */
    public function isRealEstate(): bool
    {
        return $this->sector === IndustrySector::RealEstate;
    }

    /**
     * Get a human-readable label for the store's sector/business type.
     *
     * Maps enum values to friendly names (e.g. "Real Estate Agency", "Food & Beverage Store").
     */
    public function sectorLabel(): string
    {
        return match ($this->sector) {
            IndustrySector::FoodAndBeverage => 'Food & Beverage Store',
            IndustrySector::RealEstate => 'Real Estate Agency',
            default => 'Business',
        };
    }

    /**
     * Get the panel dashboard path for this store's sector.
     *
     * Real estate stores go to the Realty panel, all others to the Lunar panel.
     */
    public function dashboardPath(): string
    {
        if ($this->isRealEstate()) {
            return '/realty/dashboard/tk_'.config('app.realty_path_token');
        }

        return '/store/dashboard/tk_'.config('app.store_path_token');
    }

    /**
     * Get the full login URL for this store's subdomain.
     *
     * Includes the unique login token in the path so each store
     * has a non-guessable login URL.
     */
    public function loginUrl(): string
    {
        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'http';
        $domain = config('app.domain');
        $port = parse_url(config('app.url'), PHP_URL_PORT);

        $url = $scheme.'://'.$this->slug.'.'.$domain;

        if ($port) {
            $url .= ':'.$port;
        }

        return $url.'/portal/'.$this->login_token.'/login';
    }

    /**
     * Generate a unique login token for this store.
     *
     * Called during store approval so each store gets
     * its own non-guessable access point.
     */
    public function generateLoginToken(): self
    {
        $this->update([
            'login_token' => 'stk_'.Str::random(24),
        ]);

        return $this;
    }
}
