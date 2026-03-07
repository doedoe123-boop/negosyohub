<?php

namespace App\Providers\Filament;

use App\Http\Responses\LunarLogoutResponse;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class LipatBahayPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('lipat-bahay')
            ->path('lipat-bahay/dashboard/tk_'.config('app.lipat_bahay_path_token'))
            ->brandName('Moving Company Dashboard')
            ->login(null)
            ->authGuard('web')
            ->colors([
                'primary' => Color::Blue,
                'danger' => Color::Rose,
                'success' => Color::Green,
                'warning' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/LipatBahay/Resources'), for: 'App\\Filament\\LipatBahay\\Resources')
            ->discoverPages(in: app_path('Filament/LipatBahay/Pages'), for: 'App\\Filament\\LipatBahay\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/LipatBahay/Widgets'), for: 'App\\Filament\\LipatBahay\\Widgets')
            ->widgets([])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        $this->app->bind(LogoutResponse::class, LunarLogoutResponse::class);
    }
}
