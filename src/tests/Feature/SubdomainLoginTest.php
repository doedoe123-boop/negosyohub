<?php

use App\Livewire\Store\StoreLogin;
use App\Models\Store;
use App\Models\User;
use App\StoreStatus;
use App\UserRole;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new \Database\Seeders\SectorSeeder)->run();

    $role = Role::firstOrCreate(['name' => 'store_owner', 'guard_name' => 'web']);

    $permissions = [
        'settings', 'settings:core', 'settings:manage-staff', 'settings:manage-attributes',
        'catalog:manage-products', 'catalog:manage-collections',
        'sales:manage-orders', 'sales:manage-customers', 'sales:manage-discounts',
    ];
    foreach ($permissions as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
    $role->syncPermissions($permissions);
});

/**
 * Build a subdomain URL for a given store slug and path.
 */
function storeUrl(string $slug, string $path = '/'): string
{
    $domain = config('app.domain', 'localhost');

    return "http://{$slug}.{$domain}{$path}";
}

/**
 * Build the token-based login path for a store.
 */
function storeLoginPath(Store $store): string
{
    return '/portal/'.$store->login_token.'/login';
}

// =========================================================
// Subdomain Resolution
// =========================================================

it('shows the store login page on a valid subdomain', function () {
    $store = Store::factory()->create([
        'slug' => 'nelsons-kitchen',
        'status' => StoreStatus::Approved,
    ]);

    $this->get(storeUrl('nelsons-kitchen', storeLoginPath($store)))
        ->assertOk()
        ->assertSee('Store Owner Login')
        ->assertSee($store->name);
});

it('returns 404 for a non-existent store subdomain', function () {
    $this->get(storeUrl('fake-store', '/portal/invalid_token/login'))
        ->assertNotFound();
});

it('returns 403 for a suspended store subdomain', function () {
    $store = Store::factory()->create([
        'slug' => 'suspended-store',
        'status' => StoreStatus::Suspended,
        'login_token' => 'stk_suspended_test_token_123',
    ]);

    $this->get(storeUrl('suspended-store', storeLoginPath($store)))
        ->assertForbidden();
});

it('returns 404 for a pending store with no token', function () {
    Store::factory()->create([
        'slug' => 'pending-store',
        'status' => StoreStatus::Pending,
        'login_token' => null,
    ]);

    $this->get(storeUrl('pending-store', '/portal/anything/login'))
        ->assertNotFound();
});

it('returns 404 for a wrong login token', function () {
    Store::factory()->create([
        'slug' => 'test-store',
        'status' => StoreStatus::Approved,
        'login_token' => 'stk_correct_token',
    ]);

    $this->get(storeUrl('test-store', '/portal/stk_wrong_token/login'))
        ->assertNotFound();
});

// =========================================================
// Authentication
// =========================================================

it('authenticates the store owner on their subdomain', function () {
    $owner = User::factory()->storeOwner()->create();
    $owner->assignRole('store_owner');
    $store = Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Approved,
    ]);

    app()->instance('currentStore', $store);

    Livewire::withoutLazyLoading()
        ->test(StoreLogin::class, ['token' => $store->login_token])
        ->set('email', $owner->email)
        ->set('password', 'password')
        ->call('authenticate');

    $this->assertAuthenticatedAs($owner);
});

it('rejects login with wrong credentials on subdomain', function () {
    $owner = User::factory()->storeOwner()->create();
    $owner->assignRole('store_owner');
    $store = Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Approved,
    ]);

    app()->instance('currentStore', $store);

    Livewire::withoutLazyLoading()
        ->test(StoreLogin::class, ['token' => $store->login_token])
        ->set('email', $owner->email)
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('rejects a user who does not own the subdomain store', function () {
    $owner = User::factory()->storeOwner()->create();
    $owner->assignRole('store_owner');
    Store::factory()->for($owner, 'owner')->create([
        'slug' => 'owners-store',
        'status' => StoreStatus::Approved,
    ]);

    // Different store for subdomain
    $otherStore = Store::factory()->create([
        'slug' => 'other-store',
        'status' => StoreStatus::Approved,
    ]);

    // Bind the other store as currentStore (simulating subdomain middleware)
    app()->instance('currentStore', $otherStore);

    Livewire::withoutLazyLoading()
        ->test(StoreLogin::class, ['token' => $otherStore->login_token])
        ->set('email', $owner->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('rejects a customer trying to login on a store subdomain', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $store = Store::factory()->create([
        'slug' => 'some-store',
        'status' => StoreStatus::Approved,
    ]);

    app()->instance('currentStore', $store);

    Livewire::withoutLazyLoading()
        ->test(StoreLogin::class, ['token' => $store->login_token])
        ->set('email', $customer->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('email');

    $this->assertGuest();
});

// =========================================================
// Subdomain Root Redirect
// =========================================================

it('redirects authenticated user from subdomain root to Lunar panel', function () {
    $owner = User::factory()->storeOwner()->create();
    $owner->assignRole('store_owner');
    $store = Store::factory()->for($owner, 'owner')->create([
        'slug' => 'test-store',
        'status' => StoreStatus::Approved,
        'sector' => 'ecommerce',
    ]);

    $this->actingAs($owner)
        ->get(storeUrl('test-store', '/'))
        ->assertRedirect('/store/dashboard/tk_'.config('app.store_path_token'));
});

it('redirects authenticated user from subdomain root to Realty panel for real estate stores', function () {
    $owner = User::factory()->storeOwner()->create();
    $owner->assignRole('store_owner');
    $store = Store::factory()->for($owner, 'owner')->create([
        'slug' => 'test-store',
        'status' => StoreStatus::Approved,
        'sector' => 'real_estate',
    ]);

    $this->actingAs($owner)
        ->get(storeUrl('test-store', '/'))
        ->assertRedirect('/realty/dashboard/tk_'.config('app.realty_path_token'));
});

it('redirects guest from subdomain root to token login', function () {
    $store = Store::factory()->create([
        'slug' => 'test-store',
        'status' => StoreStatus::Approved,
    ]);

    $this->get(storeUrl('test-store', '/'))
        ->assertRedirect('/portal/'.$store->login_token.'/login');
});
