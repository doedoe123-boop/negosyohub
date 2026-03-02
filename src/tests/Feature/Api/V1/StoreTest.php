<?php

use App\Models\Store;

// --- Store Browsing ---

it('returns a paginated list of approved stores', function () {
    Store::factory()->count(3)->create();
    Store::factory()->pending()->create();
    Store::factory()->suspended()->create();

    $this->getJson(route('api.v1.stores.index'))
        ->assertOk()
        ->assertJsonStructure(['data', 'current_page', 'total'])
        ->assertJsonCount(3, 'data');
});

it('returns only stores matching the search filter', function () {
    Store::factory()->create(['name' => 'Pizza Palace']);
    Store::factory()->create(['name' => 'Burger Barn']);

    $this->getJson(route('api.v1.stores.index', ['search' => 'pizza']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Pizza Palace');
});

it('returns a single approved store', function () {
    $store = Store::factory()->create();

    $this->getJson(route('api.v1.stores.show', $store))
        ->assertOk()
        ->assertJsonPath('id', $store->id);
});

it('returns 404 for a pending store', function () {
    $store = Store::factory()->pending()->create();

    $this->getJson(route('api.v1.stores.show', $store))
        ->assertNotFound();
});

it('returns 404 for a suspended store', function () {
    $store = Store::factory()->suspended()->create();

    $this->getJson(route('api.v1.stores.show', $store))
        ->assertNotFound();
});

it('does not require authentication to browse stores', function () {
    Store::factory()->create();

    $this->getJson(route('api.v1.stores.index'))->assertOk();
    $this->getJson(route('api.v1.stores.show', Store::factory()->create()))->assertOk();
});
