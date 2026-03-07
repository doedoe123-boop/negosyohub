<?php

namespace App\Models;

use App\IndustrySector;
use App\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Lunar\Base\LunarUser as LunarUserInterface;
use Lunar\Base\Traits\LunarUser;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, LunarUserInterface
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens;

    use HasFactory;
    use HasRoles;
    use LogsActivity;
    use LunarUser;
    use Notifiable;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'store_id',
        'paymongo_customer_id',
        'notification_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'notification_preferences' => 'array',
        ];
    }

    /**
     * Return the store owned by this user.
     */
    public function store(): HasOne
    {
        return $this->hasOne(Store::class);
    }

    /**
     * Return the store this staff member belongs to.
     */
    public function assignedStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Determine if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Determine if the user is a store owner.
     */
    public function isStoreOwner(): bool
    {
        return $this->role === UserRole::StoreOwner;
    }

    /**
     * Determine if the user is a staff member.
     */
    public function isStaff(): bool
    {
        return $this->role === UserRole::Staff;
    }

    /**
     * Determine if the user is a customer.
     */
    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    /**
     * Return the user's saved property searches.
     */
    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    /**
     * Return orders placed by this customer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Return the login history for this user.
     */
    public function loginHistory(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    /**
     * Return support tickets filed by this user.
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * Return reviews written by this user.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Return saved delivery addresses.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Return saved payment methods.
     */
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    /**
     * Get the store this user belongs to (as owner or staff).
     */
    public function getStoreForPanel(): ?Store
    {
        if ($this->isStoreOwner()) {
            return $this->store;
        }

        if ($this->isStaff()) {
            return $this->assignedStore;
        }

        return null;
    }

    /**
     * Determine if the user can access the given Filament panel.
     *
     * - Admin panel (/admin): admins only
     * - Lunar panel (/lunar): approved store owners and their staff
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->isAdmin(),
            'realty' => $this->canAccessRealtyPanel(),
            'lipat-bahay' => $this->canAccessLipatBahayPanel(),
            'lunar' => $this->canAccessLunarPanel(),
            default => false,
        };
    }

    /**
     * Check if the user can access the Lunar store panel.
     *
     * Store owners: must have an approved, regular e-commerce store.
     * Staff: must belong to an approved, regular e-commerce store.
     */
    private function canAccessLunarPanel(): bool
    {
        $store = $this->getStoreForPanel();

        if (! $store?->isApproved()) {
            return false;
        }

        return ! in_array($store->sector, [
            IndustrySector::RealEstate,
            IndustrySector::Paupahan,
            IndustrySector::LipatBahay,
        ], true);
    }

    /**
     * Check if the user can access the Real Estate / Paupahan panel.
     *
     * Only approved stores in the real_estate or paupahan sectors.
     */
    private function canAccessRealtyPanel(): bool
    {
        $store = $this->getStoreForPanel();

        if (! $store?->isApproved()) {
            return false;
        }

        return in_array($store->sector, [IndustrySector::RealEstate, IndustrySector::Paupahan], true);
    }

    /**
     * Check if the user can access the Lipat Bahay (moving company) panel.
     *
     * Only approved stores in the lipat_bahay sector.
     */
    private function canAccessLipatBahayPanel(): bool
    {
        $store = $this->getStoreForPanel();

        if (! $store?->isApproved()) {
            return false;
        }

        return $store->sector === IndustrySector::LipatBahay;
    }

    /**
     * Accessor used by Lunar's Gate::after callback to grant
     * admin users full access to all Lunar resources.
     */
    public function getAdminAttribute(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Send the customer-specific password reset notification.
     *
     * Customer accounts receive a link pointing to the Vue SPA
     * instead of the default Laravel backend reset page.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new \App\Notifications\CustomerResetPasswordNotification($token));
    }
}
