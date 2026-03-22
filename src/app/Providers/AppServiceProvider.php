<?php

namespace App\Providers;

use App\Http\Middleware\EnsureStoreSetupComplete;
use App\Http\Responses\LunarLogoutResponse;
use App\Listeners\RecordLoginHistory;
use App\Models\MovingBooking;
use App\Models\Order;
use App\Models\PropertyInquiry;
use App\Models\RentalAgreement;
use App\Observers\MovingBookingObserver;
use App\Observers\PropertyInquiryObserver;
use App\Observers\RentalAgreementObserver;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Lunar\Admin\Filament\Resources\ChannelResource as LunarChannelResource;
use Lunar\Admin\Filament\Resources\CurrencyResource as LunarCurrencyResource;
use Lunar\Admin\Filament\Resources\CustomerGroupResource as LunarCustomerGroupResource;
use Lunar\Admin\Filament\Resources\LanguageResource as LunarLanguageResource;
use Lunar\Admin\Filament\Resources\OrderResource as LunarOrderResource;
use Lunar\Admin\Filament\Resources\ProductResource as LunarProductResource;
use Lunar\Admin\Filament\Resources\StaffResource;
use Lunar\Admin\Filament\Resources\TaxClassResource as LunarTaxClassResource;
use Lunar\Admin\Filament\Resources\TaxRateResource as LunarTaxRateResource;
use Lunar\Admin\Filament\Resources\TaxZoneResource as LunarTaxZoneResource;
use Lunar\Admin\LunarPanelManager;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Facades\ModelManifest;
use Lunar\Models\Language;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Remove Lunar defaults we override with scoped versions
        // or move to the admin panel under the E-commerce group
        $this->excludeLunarResources([
            StaffResource::class,
            LunarOrderResource::class,
            LunarProductResource::class,
            LunarChannelResource::class,
            LunarCurrencyResource::class,
            LunarCustomerGroupResource::class,
            LunarLanguageResource::class,
            LunarTaxClassResource::class,
            LunarTaxRateResource::class,
            LunarTaxZoneResource::class,
        ]);

        LunarPanel::panel(fn ($panel) => $panel
            ->authGuard('web')
            ->path('store/dashboard/tk_'.config('app.store_path_token'))
            ->login(null)
            ->brandName(fn (): string => auth()->user()?->getStoreForPanel()?->name ?? config('app.name'))
            ->brandLogo(fn (): ?string => auth()->user()?->getStoreForPanel()?->logoUrl())
            ->darkModeBrandLogo(fn (): ?string => auth()->user()?->getStoreForPanel()?->logoUrl())
            ->brandLogoHeight('3rem')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\\Filament\\Pages'
            )
            ->discoverWidgets(
                in: app_path('Filament/Widgets'),
                for: 'App\\Filament\\Widgets'
            )
            ->authMiddleware([EnsureStoreSetupComplete::class])
        )->disableTwoFactorAuth()->register();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->bind(LogoutResponse::class, LunarLogoutResponse::class);

        Vite::createAssetPathsUsing(static fn (string $path): string => '/'.ltrim($path, '/'));

        RateLimiter::for('checkout-orders', function (Request $request) {
            $userKey = $request->user()?->id
                ? 'user:'.$request->user()->id
                : 'ip:'.$request->ip();

            return Limit::perMinute(12)
                ->by($userKey.':checkout-orders')
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many checkout attempts. Please wait a moment and try again.',
                    ], 429);
                });
        });

        // Register our extended Lunar models so route model binding and all
        // Lunar internals resolve App\Models\Order rather than Lunar\Models\Order.
        // Without this registration, the {order} route parameter is typed as
        // Lunar\Models\Order, which causes policy type-hint mismatches (403s).
        ModelManifest::replace(
            \Lunar\Models\Contracts\Order::class,
            Order::class,
        );

        // Record all login attempts (success + failure) for security audit
        Event::listen(Login::class, [RecordLoginHistory::class, 'handleLogin']);
        Event::listen(Failed::class, [RecordLoginHistory::class, 'handleFailed']);

        // Rental agreement observer: marks property as Rented and notifies tenant + landlord
        RentalAgreement::observe(RentalAgreementObserver::class);

        // Moving booking observer: notifies moving company on new booking, customer on status change
        MovingBooking::observe(MovingBookingObserver::class);

        // Property inquiry observer: notifies agent on new inquiry, customer on status change
        PropertyInquiry::observe(PropertyInquiryObserver::class);

        Language::saved(function (): void {
            Cache::forget('localization.active-locales');
            Cache::forget('localization.default-locale');
            Cache::forget('localization.active-locales.catalog');
            Cache::forget('localization.default-locale.catalog');
        });

        Language::deleted(function (): void {
            Cache::forget('localization.active-locales');
            Cache::forget('localization.default-locale');
            Cache::forget('localization.active-locales.catalog');
            Cache::forget('localization.default-locale.catalog');
        });
    }

    /**
     * Remove specific resources from Lunar's default resource list.
     *
     * @param  array<class-string>  $resources
     */
    private function excludeLunarResources(array $resources): void
    {
        $current = LunarPanelManager::getResources();
        $filtered = array_values(array_diff($current, $resources));

        // Use reflection to set the protected static property
        $reflection = new \ReflectionClass(LunarPanelManager::class);
        $property = $reflection->getProperty('resources');
        $property->setValue(null, $filtered);
    }
}
