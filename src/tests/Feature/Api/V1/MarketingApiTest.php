<?php

use App\AdPlacement;
use App\Models\Advertisement;
use App\Models\Announcement;
use App\Models\Coupon;
use App\Models\FeaturedListing;
use App\Models\Promotion;
use App\Models\Sector;
use App\Models\Store;
use App\Models\User;
use App\StoreStatus;
use Illuminate\Support\Facades\Cache;

// ── Advertisements ────────────────────────────────────────────────────

it('returns only active advertisements', function () {
    Advertisement::factory()->active()->count(2)->create();
    Advertisement::factory()->create(); // draft — should be excluded
    Advertisement::factory()->expired()->create(); // expired — excluded

    $this->getJson(route('api.v1.advertisements.index'))
        ->assertOk()
        ->assertJsonCount(2);
});

it('filters advertisements by placement', function () {
    Advertisement::factory()->active()->homeBanner()->create();
    Advertisement::factory()->active()->create(['placement' => AdPlacement::StorePage]);

    $this->getJson(route('api.v1.advertisements.index', ['placement' => 'home_banner']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.placement', 'home_banner');
});

it('returns empty array when no active ads exist', function () {
    $this->getJson(route('api.v1.advertisements.index'))
        ->assertOk()
        ->assertJsonCount(0);
});

// ── Announcements ────────────────────────────────────────────────────

it('returns only active non-expired announcements', function () {
    Announcement::factory()->count(2)->create(); // active by default
    Announcement::factory()->inactive()->create();
    Announcement::factory()->expired()->create();

    $this->getJson(route('api.v1.announcements.index'))
        ->assertOk()
        ->assertJsonCount(2);
});

it('filters announcements by audience', function () {
    Announcement::factory()->forCustomers()->create();
    Announcement::factory()->forStoreOwners()->create();

    $this->getJson(route('api.v1.announcements.index', ['audience' => 'customers']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.audience', 'customers');
});

it('returns announcement content as raw HTML', function () {
    Announcement::factory()->create([
        'content' => '<p><strong>Sale!</strong></p>',
    ]);

    $this->getJson(route('api.v1.announcements.index'))
        ->assertOk()
        ->assertJsonPath('0.content', '<p><strong>Sale!</strong></p>');
});

// ── Promotions ───────────────────────────────────────────────────────

it('returns only active promotions', function () {
    Promotion::factory()->active()->count(3)->create();
    Promotion::factory()->create(); // draft

    $this->getJson(route('api.v1.promotions.index'))
        ->assertOk()
        ->assertJsonCount(3);
});

it('returns promotion discount fields', function () {
    Promotion::factory()->active()->create([
        'discount_percentage' => 25,
    ]);

    $this->getJson(route('api.v1.promotions.index'))
        ->assertOk()
        ->assertJsonPath('0.discount_percentage', 25);
});

// ── Coupons ──────────────────────────────────────────────────────────

it('validates a usable coupon code', function () {
    $coupon = Coupon::factory()->active()->percentage(15)->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.coupons.validate'), ['code' => $coupon->code])
        ->assertOk()
        ->assertJsonPath('code', $coupon->code)
        ->assertJsonPath('value', 15);
});

it('rejects an invalid coupon code', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.coupons.validate'), ['code' => 'NOTREAL'])
        ->assertUnprocessable();
});

it('rejects a draft coupon', function () {
    $coupon = Coupon::factory()->create(); // draft by default
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.coupons.validate'), ['code' => $coupon->code])
        ->assertUnprocessable();
});

it('requires authentication to validate a coupon', function () {
    $this->postJson(route('api.v1.coupons.validate'), ['code' => 'TEST'])
        ->assertUnauthorized();
});

// ── Featured Listings ────────────────────────────────────────────────

it('returns only active featured listings with their featurable', function () {
    FeaturedListing::factory()->active()->featuredStore()->count(2)->create();
    FeaturedListing::factory()->featuredStore()->create(); // draft

    $this->getJson(route('api.v1.featured-listings.index'))
        ->assertOk()
        ->assertJsonCount(2);
});

it('filters featured listings by type', function () {
    FeaturedListing::factory()->active()->featuredStore()->create();
    FeaturedListing::factory()->active()->featuredService()->create();

    $this->getJson(route('api.v1.featured-listings.index', ['type' => 'store']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.featured_type', 'store');
});

it('returns public market insight aggregates', function () {
    Cache::forget('market_insights');

    Sector::factory()->create([
        'name' => 'E-Commerce',
        'slug' => 'ecommerce',
        'is_active' => true,
    ]);
    Sector::factory()->create([
        'name' => 'Real Estate',
        'slug' => 'real_estate',
        'is_active' => true,
    ]);

    User::factory()->count(3)->create();

    Store::factory()->create([
        'status' => StoreStatus::Approved,
        'sector' => 'ecommerce',
        'business_permit' => 'permit-a',
        'address' => [
            'line_one' => '1 Emerald Street',
            'city' => 'Pasig',
        ],
    ]);
    Store::factory()->create([
        'status' => StoreStatus::Approved,
        'sector' => 'ecommerce',
        'business_permit' => 'permit-b',
        'address' => [
            'line_one' => '2 Emerald Street',
            'city' => 'Pasig',
        ],
    ]);
    Store::factory()->create([
        'status' => StoreStatus::Approved,
        'sector' => 'real_estate',
        'business_permit' => null,
        'address' => [
            'line_one' => '3 Sapphire Road',
            'city' => 'Bacoor',
        ],
    ]);

    $response = $this->getJson(route('api.v1.market-insights'))
        ->assertOk()
        ->assertJsonPath('stats.approved_suppliers', 3)
        ->assertJsonPath('stats.active_sectors', 2)
        ->assertJsonPath('stats.cities_covered', 2)
        ->assertJsonPath('health.permit_compliance_rate', 67);

    expect($response->json('top_sectors.0.name'))->toBe('E-Commerce')
        ->and($response->json('top_sectors.0.total'))->toBe(2)
        ->and($response->json('top_cities.0.city'))->toBe('Pasig')
        ->and($response->json('top_cities.0.total'))->toBe(2);
});
