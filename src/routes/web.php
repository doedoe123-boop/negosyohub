<?php

use App\Http\Controllers\SupplierProfileController;
use App\Livewire\SectorBrowse;
use App\Livewire\Store\SectorSelection;
use App\Livewire\Store\StoreOwnerRegistration;
use App\Livewire\StoreDirectory;
use App\Models\GlobalSeoSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// =========================================================
// Store subdomain routes (must load first so domain-constrained
// routes take priority over unconstrained ones)
// =========================================================
require __DIR__.'/store.php';

// =========================================================
// Main domain routes
// =========================================================
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/robots.txt', function () {
    $content = GlobalSeoSetting::current()->robots_txt_content
        ?? "User-agent: *\nAllow: /";

    return response($content, 200)->header('Content-Type', 'text/plain');
})->name('robots');

// Browse stores (all verified stores) — Livewire-powered search, filter, sort, pagination
Route::get('/stores', StoreDirectory::class)->name('stores.index');

// Deals & Offers page
Route::get('/deals', function () {
    return view('deals.index');
})->name('deals.index');

// Market Insights page
Route::get('/insights', function () {
    return view('insights.index');
})->name('insights.index');

// Public supplier profile (approved stores only)
Route::get('/suppliers/{slug}', [SupplierProfileController::class, 'show'])->name('suppliers.show');

// Public sector/industry browsing page
Route::get('/sector', SectorBrowse::class)->name('sector.browse');

// Public legal pages (Terms, Privacy Policy, etc.)
Route::get('/legal/{slug}', function (string $slug) {
    $page = \App\Models\LegalPage::published()->where('slug', $slug)->firstOrFail();

    return view('legal.show', compact('page'));
})->name('legal.show');

// Guest auth & registration routes
Route::middleware('guest')->group(function () {
    // Customer login & register will be served via API in Phase 5 (storefront)
    Route::get('/login', fn () => abort(404))->name('login');
    Route::get('/register', fn () => abort(404))->name('register');
    Route::get('/register/sector', SectorSelection::class)->name('register.sector');
    Route::get('/register/store-owner/success', fn () => view('store.registration-success'))->name('register.store-owner.success');
    Route::get('/register/store-owner/{sector}', StoreOwnerRegistration::class)->name('register.store-owner');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', function () {
        Auth::guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();

        return redirect('/');
    })->name('logout');

    // Store owner dashboard — approved stores go to the correct panel
    Route::middleware('role:store_owner')->group(function () {
        Route::get('/store/dashboard', function () {
            $store = auth()->user()->store;

            if ($store?->isApproved()) {
                return redirect($store->dashboardPath());
            }

            return view('store.dashboard');
        })->name('store.dashboard');

        Route::get('/store/pending', function () {
            return view('store.dashboard');
        })->name('store.pending');
    });
});
