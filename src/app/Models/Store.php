<?php

namespace App\Models;

use App\PayoutMethod;
use App\SectorTemplate;
use App\StoreStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Lunar\Models\Collection;
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
 * @property ?string $banner
 * @property ?array $address
 * @property ?string $id_type
 * @property ?string $id_number
 * @property ?string $business_permit
 * @property ?array $compliance_documents
 * @property float $commission_rate
 * @property StoreStatus $status
 * @property ?string $sector
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
 * @property ?PayoutMethod $payout_method
 * @property ?array $payout_details
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
        'banner',
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
        'payout_method',
        'payout_details',
        'suspended_at',
        'suspension_reason',
        'setup_completed_at',
    ];

    /** @var list<string> */
    protected $appends = ['logo_url', 'banner_url', 'agent_photo_url', 'agent_name', 'sector_template', 'sector_label', 'sector_theme'];

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
            'sector' => 'string',
            'agent_certifications' => 'array',
            'agent_specializations' => 'array',
            'social_links' => 'array',
            'default_interest_rate' => 'decimal:2',
            'default_down_payment_percent' => 'decimal:2',
            'operating_hours' => 'array',
            'payout_method' => PayoutMethod::class,
            'payout_details' => 'encrypted:array',
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
     * The marketplace categories (Lunar Collections) this store belongs to.
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'store_collection');
    }

    /**
     * Look up the Sector model record for this store's sector slug.
     *
     * Uses slug-based resolution (not FK) for backward compatibility.
     */
    public function sectorModel(): ?Sector
    {
        if (! $this->sector) {
            return null;
        }

        return Sector::query()->where('slug', $this->sector)->first();
    }

    /**
     * Get the SectorTemplate for this store via its Sector record.
     */
    public function template(): ?SectorTemplate
    {
        return $this->sectorModel()?->template;
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
     * Moving service add-ons offered by a Lipat Bahay store.
     */
    public function movingAddOns(): HasMany
    {
        return $this->hasMany(MovingAddOn::class);
    }

    /**
     * Moving bookings received by a Lipat Bahay store.
     */
    public function movingBookings(): HasMany
    {
        return $this->hasMany(MovingBooking::class);
    }

    /**
     * Rental agreements created by a Paupahan store.
     */
    public function rentalAgreements(): HasMany
    {
        return $this->hasMany(RentalAgreement::class);
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
     * Return a public URL for the store banner, or null if unset.
     *
     * Handles both legacy http URLs and storage-relative paths.
     */
    public function bannerUrl(): ?string
    {
        if (! $this->banner) {
            return null;
        }

        if (str_starts_with($this->banner, 'http')) {
            return $this->banner;
        }

        return asset('storage/'.$this->banner);
    }

    /**
     * Return a public URL for the agent's photo, or null if unset.
     *
     * Handles both legacy http URLs and storage-relative paths.
     */
    public function agentPhotoUrl(): ?string
    {
        if (! $this->agent_photo) {
            return null;
        }

        if (str_starts_with($this->agent_photo, 'http')) {
            return $this->agent_photo;
        }

        return asset('storage/'.$this->agent_photo);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logoUrl();
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->bannerUrl();
    }

    public function getAgentPhotoUrlAttribute(): ?string
    {
        return $this->agentPhotoUrl();
    }

    public function getAgentNameAttribute(): ?string
    {
        return $this->owner?->name;
    }

    public function getSectorTemplateAttribute(): ?string
    {
        return $this->template()?->value;
    }

    public function getSectorLabelAttribute(): string
    {
        return $this->sectorLabel();
    }

    public function getSectorThemeAttribute(): ?string
    {
        return $this->template()?->themeGradient();
    }

    /**
     * Determine if the store belongs to the real estate sector.
     */
    public function isRealEstate(): bool
    {
        return $this->template() === SectorTemplate::RealEstate;
    }

    /**
     * Determine if the store belongs to the Paupahan (rental) sector.
     */
    public function isPaupahan(): bool
    {
        return $this->template() === SectorTemplate::Rental;
    }

    /**
     * Determine if the store belongs to the Lipat Bahay (moving service) sector.
     */
    public function isLipatBahay(): bool
    {
        return $this->template() === SectorTemplate::Logistics;
    }

    /**
     * Determine if the store uses the property-based Realty panel.
     *
     * Both Real Estate agencies and Paupahan (rental) landlords manage
     * property listings, so they share the same panel.
     */
    public function usesRealtyPanel(): bool
    {
        return $this->template()?->panelId() === 'realty';
    }

    /**
     * Get a human-readable label for the store's sector/business type.
     */
    public function sectorLabel(): string
    {
        return $this->sectorModel()?->name ?? $this->template()?->label() ?? 'Business';
    }

    /**
     * Get the panel dashboard path for this store's sector.
     *
     * Uses the sector template's panel ID to determine the correct path.
     */
    public function dashboardPath(): string
    {
        $panelId = $this->template()?->panelId();

        return match ($panelId) {
            'realty' => '/realty/dashboard/tk_'.config('app.realty_path_token'),
            'lipat-bahay' => '/lipat-bahay/dashboard/tk_'.config('app.lipat_bahay_path_token'),
            default => '/store/dashboard/tk_'.config('app.store_path_token'),
        };
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
