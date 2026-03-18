<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AdvertisementController;
use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\DevelopmentController;
use App\Http\Controllers\Api\V1\FeaturedListingController;
use App\Http\Controllers\Api\V1\GlobalSearchController;
use App\Http\Controllers\Api\V1\HomepageStatsController;
use App\Http\Controllers\Api\V1\MoverController;
use App\Http\Controllers\Api\V1\MovingBookingController;
use App\Http\Controllers\Api\V1\MovingReviewController;
use App\Http\Controllers\Api\V1\OpenHouseController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PromotionController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\SavedSearchController;
use App\Http\Controllers\Api\V1\SeoController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\SupportTicketController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Webhooks\PayMongoController;
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
        Route::get('/search', GlobalSearchController::class)->name('search');
        Route::get('/homepage-stats', HomepageStatsController::class)->name('homepage.stats');
        Route::get('/seo/global', [SeoController::class, 'global'])->name('seo.global');
        Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
        Route::get('/stores/{store:slug}', [StoreController::class, 'show'])->name('stores.show');
        Route::get('/stores/{store:slug}/products', [ProductController::class, 'storeProducts'])->name('products.store');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');

        Route::get('/stores/{store:slug}/properties', [StoreController::class, 'storeProperties'])->name('properties.store');

        Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
        Route::get('/properties/{slug}', [PropertyController::class, 'show'])->name('properties.show');
        Route::post('/properties/{property:slug}/inquiries', [PropertyController::class, 'submitInquiry'])->name('properties.inquiries.store');
        Route::get('/properties/{property:slug}/open-houses', [PropertyController::class, 'openHouses'])->name('properties.open-houses');
        Route::post('/properties/{property:slug}/track', [PropertyController::class, 'track'])->name('properties.track');

        // Developments (Real Estate projects)
        Route::get('/developments', [DevelopmentController::class, 'index'])->name('developments.index');
        Route::get('/developments/{slug}', [DevelopmentController::class, 'show'])->name('developments.show');

        // Open House RSVP
        Route::post('/open-houses/{openHouse}/rsvp', [OpenHouseController::class, 'rsvp'])->name('open-houses.rsvp');

        // Movers (Lipat Bahay / Moving Service)
        Route::get('/movers', [MoverController::class, 'index'])->name('movers.index');
        Route::get('/movers/{store:slug}', [MoverController::class, 'show'])->name('movers.show');

        // Marketing (public, read-only)
        Route::get('/advertisements', [AdvertisementController::class, 'index'])->name('advertisements.index');
        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');
        Route::get('/featured-listings', [FeaturedListingController::class, 'index'])->name('featured-listings.index');

        // Reviews (public, read-only)
        Route::get('/products/{product}/reviews', [ReviewController::class, 'productIndex'])->name('products.reviews.index');
        Route::get('/properties/{property:slug}/reviews', [ReviewController::class, 'propertyIndex'])->name('properties.reviews.index');
    });

    // ── Authenticated customer endpoints ──────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:180,1'])->group(function () {
        // Auth
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/user', [AuthController::class, 'user'])->name('auth.user');

        // User profile, password, settings, account
        Route::patch('/user', [UserController::class, 'update'])->name('user.update');
        Route::patch('/user/password', [UserController::class, 'changePassword'])->name('user.password');
        Route::patch('/user/settings', [UserController::class, 'updateSettings'])->name('user.settings');
        Route::delete('/user', [UserController::class, 'destroy'])->name('user.destroy');

        // User inquiries (cross-sector dashboard)
        Route::get('/user/inquiries', [UserController::class, 'inquiries'])->name('user.inquiries');
        Route::get('/user/rental-agreements', [UserController::class, 'rentalAgreements'])->name('user.rentalAgreements');
        Route::patch('/user/rental-agreements/{id}', [UserController::class, 'updateRentalAgreement'])->name('user.rentalAgreements.update');

        // User notifications
        Route::get('/user/notifications', [UserController::class, 'notifications'])->name('user.notifications');
        Route::patch('/user/notifications/{id}/read', [UserController::class, 'markNotificationRead'])->name('user.notifications.read');
        Route::post('/user/notifications/read-all', [UserController::class, 'markAllNotificationsRead'])->name('user.notifications.readAll');

        // Delivery addresses
        Route::get('/user/addresses', [AddressController::class, 'index'])->name('addresses.index');
        Route::post('/user/addresses', [AddressController::class, 'store'])->name('addresses.store');
        Route::patch('/user/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
        Route::delete('/user/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
        Route::patch('/user/addresses/{address}/default', [AddressController::class, 'setDefault'])->name('addresses.default');

        // Saved payment methods
        Route::get('/user/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::post('/user/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
        Route::delete('/user/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');
        Route::patch('/user/payment-methods/{paymentMethod}/default', [PaymentMethodController::class, 'setDefault'])->name('payment-methods.default');

        // Cart
        Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
        Route::post('/cart/lines', [CartController::class, 'addLine'])->name('cart.lines.store');
        Route::patch('/cart/lines/{lineId}', [CartController::class, 'updateLine'])->name('cart.lines.update');
        Route::delete('/cart/lines/{lineId}', [CartController::class, 'removeLine'])->name('cart.lines.destroy');
        Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
        Route::get('/cart/shipping-options', [CartController::class, 'shippingOptions'])->name('cart.shipping-options');
        Route::post('/cart/shipping-option', [CartController::class, 'setShippingOption'])->name('cart.shipping-option');
        Route::post('/cart/address', [CartController::class, 'setAddress'])->name('cart.address');

        // Coupon validation
        Route::post('/coupons/validate', [CouponController::class, 'validate'])->name('coupons.validate');

        // Orders — read
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

        // Orders — customer cancel (tighter throttle to resist abuse)
        Route::middleware('throttle:10,1')->group(function () {
            Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        });

        // Orders — place new (strict throttle: prevents double-click duplicates & abuse)
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
            // Payment intent creation shares the same tight throttle as order placement.
            Route::post('/orders/{order}/intent', [PaymentController::class, 'intent'])->name('orders.intent');
        });

        // PayPal payment flow (throttled like order placement)
        Route::middleware('throttle:10,1')->prefix('paypal')->group(function () {
            Route::post('/create-order', [PaymentController::class, 'paypalCreateOrder'])->name('paypal.create-order');
            Route::post('/capture-order', [PaymentController::class, 'paypalCaptureOrder'])->name('paypal.capture-order');
        });

        // Moving bookings (Lipat Bahay)
        Route::get('/moving-bookings', [MovingBookingController::class, 'index'])->name('moving-bookings.index');
        Route::get('/moving-bookings/{movingBooking}', [MovingBookingController::class, 'show'])->name('moving-bookings.show');
        Route::patch('/moving-bookings/{movingBooking}/cancel', [MovingBookingController::class, 'cancel'])->name('moving-bookings.cancel');
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('/moving-bookings', [MovingBookingController::class, 'store'])->name('moving-bookings.store');
            Route::post('/moving-bookings/{movingBooking}/review', [MovingReviewController::class, 'store'])->name('moving-bookings.review.store');

            // Product & property reviews
            Route::post('/products/{product}/reviews', [ReviewController::class, 'productStore'])->name('products.reviews.store');
            Route::post('/properties/{property:slug}/reviews', [ReviewController::class, 'propertyStore'])->name('properties.reviews.store');
        });

        // Quick inquiry — own bucket so dashboard page-loads don't eat the limit
        Route::middleware('throttle:5,1,quick-inquiry')->group(function () {
            Route::post('/properties/{property:slug}/quick-inquiry', [PropertyController::class, 'quickInquiry'])->name('properties.quick-inquiry');
        });
        Route::middleware('throttle:30,1')->group(function () {
            Route::patch('/moving-bookings/{movingBooking}/status', [MovingBookingController::class, 'updateStatus'])->name('moving-bookings.status');
        });

        // Support Tickets
        Route::get('/user/support-tickets', [SupportTicketController::class, 'index'])->name('support-tickets.index');
        Route::post('/user/support-tickets', [SupportTicketController::class, 'store'])->name('support-tickets.store');
        Route::get('/user/support-tickets/{id}', [SupportTicketController::class, 'show'])->name('support-tickets.show');

        // Saved Searches
        Route::get('/user/saved-searches', [SavedSearchController::class, 'index'])->name('saved-searches.index');
        Route::post('/user/saved-searches', [SavedSearchController::class, 'store'])->name('saved-searches.store');
        Route::delete('/user/saved-searches/{savedSearch}', [SavedSearchController::class, 'destroy'])->name('saved-searches.destroy');
        Route::patch('/user/saved-searches/{savedSearch}/toggle', [SavedSearchController::class, 'toggle'])->name('saved-searches.toggle');

        // Orders — store-owner progression (store owner / admin only, enforced by policy)
        Route::middleware('throttle:30,1')->group(function () {
            Route::patch('/orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
            Route::patch('/orders/{order}/prepare', [OrderController::class, 'prepare'])->name('orders.prepare');
            Route::patch('/orders/{order}/ready', [OrderController::class, 'markReady'])->name('orders.ready');
            Route::patch('/orders/{order}/deliver', [OrderController::class, 'deliver'])->name('orders.deliver');
        });
    });

});

// ── PayMongo Webhooks (public — HMAC-verified, not auth:sanctum) ──────────────
//
// PayMongo calls this URL server-to-server.  Authentication is the HMAC-SHA256
// signature in the Paymongo-Signature header, verified by PayMongoService.
//
Route::post('/webhooks/paymongo', [PayMongoController::class, 'handle'])
    ->name('webhooks.paymongo');
