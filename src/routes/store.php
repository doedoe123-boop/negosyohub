<?php

use App\Livewire\Store\StoreForgotPassword;
use App\Livewire\Store\StoreLogin;
use App\Livewire\Store\StoreResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// =========================================================
// Store subdomain routes ({slug}.localhost)
// =========================================================
Route::domain('{storeSlug}.'.config('app.domain'))
    ->middleware(['web', 'store.subdomain'])
    ->group(function () {
        // Token-protected store login page (each store has a unique token)
        Route::get('/portal/{token}/login', StoreLogin::class)
            ->middleware('guest')
            ->name('store.subdomain.login');

        // Subdomain root redirects to login with token
        Route::get('/', function () {
            if (Auth::check()) {
                $store = app('currentStore');

                return redirect($store->dashboardPath());
            }

            $store = app('currentStore');

            if ($store && $store->login_token) {
                return redirect('/portal/'.$store->login_token.'/login');
            }

            // Fallback if store has no token yet (pending stores)
            return abort(404);
        })->name('store.subdomain.home');

        // Forgot password
        Route::get('/portal/{token}/forgot-password', StoreForgotPassword::class)
            ->middleware('guest')
            ->name('store.subdomain.password.request');

        // Reset password (link from email)
        Route::get('/portal/{token}/reset-password/{resetToken}', StoreResetPassword::class)
            ->middleware('guest')
            ->name('store.subdomain.password.reset');

        // Logout from subdomain
        Route::post('/logout', function () {
            Auth::guard('web')->logout();

            session()->invalidate();
            session()->regenerateToken();

            $store = app('currentStore');

            if ($store && $store->login_token) {
                return redirect('/portal/'.$store->login_token.'/login');
            }

            return redirect('/');
        })->middleware('auth')->name('store.subdomain.logout');
    });
