<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\StoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1 (Customer Storefront)
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1 by the RouteServiceProvider.
| This API is for the customer-facing Vue SPA only.
|
| Seller registration and store management live in the Blade portal
| at /register/sector (handled by Livewire in routes/web.php).
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // ── Guest endpoints (tight throttle to prevent brute-force) ──────────
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');
    });

    // ── Public browse (no auth required) ─────────────────────────────────
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
        Route::get('/stores/{store:slug}', [StoreController::class, 'show'])->name('stores.show');
        Route::get('/stores/{store:slug}/products', [ProductController::class, 'storeProducts'])->name('products.store');

        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');

        Route::get('/stores/{store:slug}/properties', [StoreController::class, 'storeProperties'])->name('properties.store');

        Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
        Route::get('/properties/{slug}', [PropertyController::class, 'show'])->name('properties.show');
        Route::post('/properties/{property:slug}/inquiries', [PropertyController::class, 'submitInquiry'])->name('properties.inquiries.store');
        Route::get('/properties/{property:slug}/open-houses', [PropertyController::class, 'openHouses'])->name('properties.open-houses');
    });

    // ── Authenticated customer endpoints ──────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        // Auth
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/user', [AuthController::class, 'user'])->name('auth.user');

        // Orders
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
        Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    });

});
