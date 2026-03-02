<?php

namespace App\Providers;

use App\Filament\Resources\TaxZoneResource\Pages\CreateTaxZone;
use App\Filament\Resources\TaxZoneResource\Pages\EditTaxZone;
use App\Filament\Resources\TaxZoneResource\Pages\ListTaxZones;
use App\Http\Middleware\EnsureStoreSetupComplete;
use App\Http\Responses\LunarLogoutResponse;
use App\Listeners\RecordLoginHistory;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Lunar\Admin\Filament\Resources\OrderResource as LunarOrderResource;
use Lunar\Admin\Filament\Resources\ProductResource as LunarProductResource;
use Lunar\Admin\Filament\Resources\StaffResource;
use Lunar\Admin\Filament\Resources\TaxZoneResource as LunarTaxZoneResource;
use Lunar\Admin\LunarPanelManager;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Shipping\Filament\Resources\ShippingExclusionListResource\Pages\EditShippingExclusionList;
use Lunar\Shipping\Filament\Resources\ShippingExclusionListResource\Pages\ListShippingExclusionLists;
use Lunar\Shipping\Filament\Resources\ShippingMethodResource\Pages\EditShippingMethod;
use Lunar\Shipping\Filament\Resources\ShippingMethodResource\Pages\ListShippingMethod;
use Lunar\Shipping\Filament\Resources\ShippingMethodResource\Pages\ManageShippingMethodAvailability;
use Lunar\Shipping\Filament\Resources\ShippingZoneResource\Pages\EditShippingZone;
use Lunar\Shipping\Filament\Resources\ShippingZoneResource\Pages\ListShippingZones;
use Lunar\Shipping\Filament\Resources\ShippingZoneResource\Pages\ManageShippingExclusions;
use Lunar\Shipping\Filament\Resources\ShippingZoneResource\Pages\ManageShippingRates;
use Lunar\Shipping\ShippingPlugin;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Remove Lunar defaults we override with scoped versions
        $this->excludeLunarResources([
            StaffResource::class,
            LunarOrderResource::class,
            LunarProductResource::class,
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
            ->plugin(new ShippingPlugin)
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
            ->livewireComponents([
                ListTaxZones::class,
                CreateTaxZone::class,
                EditTaxZone::class,
                // Shipping plugin pages
                ListShippingZones::class,
                EditShippingZone::class,
                ManageShippingRates::class,
                ManageShippingExclusions::class,
                ListShippingMethod::class,
                EditShippingMethod::class,
                ManageShippingMethodAvailability::class,
                ListShippingExclusionLists::class,
                EditShippingExclusionList::class,
            ])
            ->authMiddleware([EnsureStoreSetupComplete::class])
        )->disableTwoFactorAuth()->register();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->bind(LogoutResponse::class, LunarLogoutResponse::class);

        // Record all login attempts (success + failure) for security audit
        Event::listen(Login::class, [RecordLoginHistory::class, 'handleLogin']);
        Event::listen(Failed::class, [RecordLoginHistory::class, 'handleFailed']);
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
