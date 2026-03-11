<?php

use App\Models\Property;
use App\Models\Store;
use App\PropertyStatus;

// ─── Property index ───────────────────────────────────────────────────────────

it('returns a paginated list of active properties', function () {
    $store = Store::factory()->create();

    Property::factory()->count(3)->create(['store_id' => $store->id]);
    Property::factory()->draft()->create(['store_id' => $store->id]);
    Property::factory()->sold()->create(['store_id' => $store->id]);

    $this->getJson(route('api.v1.properties.index'))
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'total']])
        ->assertJsonCount(3, 'data');
});

it('excludes draft properties from the index', function () {
    $store = Store::factory()->create();

    Property::factory()->create(['store_id' => $store->id, 'status' => PropertyStatus::Active]);
    Property::factory()->draft()->create(['store_id' => $store->id]);

    $this->getJson(route('api.v1.properties.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('excludes properties without a published_at date', function () {
    $store = Store::factory()->create();

    Property::factory()->create([
        'store_id' => $store->id,
        'status' => PropertyStatus::Active,
        'published_at' => now(),
    ]);
    Property::factory()->create([
        'store_id' => $store->id,
        'status' => PropertyStatus::Active,
        'published_at' => null,
    ]);

    $this->getJson(route('api.v1.properties.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns property fields in the list response', function () {
    $store = Store::factory()->create();

    $property = Property::factory()->create([
        'store_id' => $store->id,
        'title' => 'Modern Condo in BGC',
    ]);

    $this->getJson(route('api.v1.properties.index'))
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Modern Condo in BGC')
        ->assertJsonStructure(['data' => [['id', 'title', 'slug', 'property_type', 'listing_type', 'status', 'price']]]);
});

it('filters properties by type', function () {
    $store = Store::factory()->create();

    Property::factory()->create(['store_id' => $store->id, 'property_type' => 'house']);
    Property::factory()->create(['store_id' => $store->id, 'property_type' => 'condo']);

    $this->getJson(route('api.v1.properties.index', ['type' => 'house']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.property_type', 'house');
});

it('filters properties by listing_type', function () {
    $store = Store::factory()->create();

    Property::factory()->create(['store_id' => $store->id, 'listing_type' => 'for_sale']);
    Property::factory()->create(['store_id' => $store->id, 'listing_type' => 'for_rent']);

    $this->getJson(route('api.v1.properties.index', ['listing_type' => 'for_sale']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.listing_type', 'for_sale');
});

it('filters properties by search term', function () {
    $store = Store::factory()->create();

    Property::factory()->create(['store_id' => $store->id, 'title' => 'Beach House in Batangas', 'city' => 'Batangas City']);
    Property::factory()->create(['store_id' => $store->id, 'title' => 'Studio Condo in Makati', 'city' => 'Makati']);

    $this->getJson(route('api.v1.properties.index', ['search' => 'batangas']))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters properties by minimum price', function () {
    $store = Store::factory()->create();

    Property::factory()->create(['store_id' => $store->id, 'price' => 1_000_000]);
    Property::factory()->create(['store_id' => $store->id, 'price' => 5_000_000]);
    Property::factory()->create(['store_id' => $store->id, 'price' => 10_000_000]);

    $this->getJson(route('api.v1.properties.index', ['min_price' => 5_000_000]))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters by featured flag', function () {
    $store = Store::factory()->create();

    Property::factory()->featured()->create(['store_id' => $store->id]);
    Property::factory()->create(['store_id' => $store->id, 'is_featured' => false]);

    $this->getJson(route('api.v1.properties.index', ['featured' => true]))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('does not require authentication to browse properties', function () {
    $store = Store::factory()->create();
    Property::factory()->create(['store_id' => $store->id]);

    $this->getJson(route('api.v1.properties.index'))->assertOk();
});

// ─── Property show ────────────────────────────────────────────────────────────

it('returns a single active property by slug', function () {
    $store = Store::factory()->create();
    $property = Property::factory()->create([
        'store_id' => $store->id,
        'title' => 'Luxury Penthouse BGC',
    ]);

    $this->getJson(route('api.v1.properties.show', $property->slug))
        ->assertOk()
        ->assertJsonPath('data.id', $property->id)
        ->assertJsonPath('data.title', 'Luxury Penthouse BGC')
        ->assertJsonStructure(['data' => ['id', 'title', 'slug', 'description', 'property_type', 'listing_type', 'price', 'city', 'province', 'images', 'features']]);
});

it('increments the view count when a property is shown', function () {
    $store = Store::factory()->create();
    $property = Property::factory()->create(['store_id' => $store->id, 'views_count' => 10]);

    $this->getJson(route('api.v1.properties.show', $property->slug))->assertOk();

    expect($property->fresh()->views_count)->toBe(11);
});

it('returns 404 for a non-existent property slug', function () {
    $this->getJson(route('api.v1.properties.show', 'non-existent-slug'))
        ->assertNotFound();
});

it('returns 404 for a draft property', function () {
    $store = Store::factory()->create();
    $property = Property::factory()->draft()->create(['store_id' => $store->id]);

    $this->getJson(route('api.v1.properties.show', $property->slug))
        ->assertNotFound();
});

it('returns 404 for a sold property', function () {
    $store = Store::factory()->create();
    $property = Property::factory()->sold()->create(['store_id' => $store->id]);

    $this->getJson(route('api.v1.properties.show', $property->slug))
        ->assertNotFound();
});
