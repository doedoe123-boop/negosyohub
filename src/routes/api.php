<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrderController;
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
    });

    // ── Public browse (no auth required) ─────────────────────────────────
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
        Route::get('/stores/{store}', [StoreController::class, 'show'])->name('stores.show');
    });

    // ── Authenticated customer endpoints ──────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        // Auth
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/user', [AuthController::class, 'user'])->name('auth.user');

        // Orders
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    });

});
