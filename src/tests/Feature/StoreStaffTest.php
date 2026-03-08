<?php

use App\Livewire\Store\StoreLogin;
use App\Models\Store;
use App\Models\User;
use App\StoreStatus;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Create roles
    $storeOwnerRole = Role::firstOrCreate(['name' => 'store_owner', 'guard_name' => 'web']);
    $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

    // Create permissions
    $permissions = [
        'settings', 'settings:core', 'settings:manage-staff', 'settings:manage-attributes',
        'catalog:manage-products', 'catalog:manage-collections',
        'sales:manage-orders', 'sales:manage-customers', 'sales:manage-discounts',
    ];
    foreach ($permissions as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }

    $storeOwnerRole->syncPermissions($permissions);

    // Staff get limited permissions
    $staffRole->syncPermissions([
        'catalog:manage-products',
        'catalog:manage-collections',
        'sales:manage-orders',
        'sales:manage-customers',
    ]);
});

/**
 * Build a subdomain URL for a given store slug and path.
 */
function staffStoreUrl(string $slug, string $path = '/'): string
{
    $domain = config('app.domain', 'localhost');

    return "http://{$slug}.{$domain}{$path}";
}

/**
 * Create a store owner with an approved store.
 */
function createOwnerWithStore(string $slug = 'test-store'): array
{
    $owner = User::factory()->storeOwner()->create();
    $owner->assignRole('store_owner');
    $store = Store::factory()->for($owner, 'owner')->create([
        'slug' => $slug,
        'status' => StoreStatus::Approved,
        'sector' => 'ecommerce',
    ]);

    return [$owner, $store];
}

/**
 * Create a staff member belonging to a store.
 */
function createStaffForStore(Store $store, array $overrides = []): User
{
    $staff = User::factory()->staff()->create(array_merge([
        'store_id' => $store->id,
    ], $overrides));
    $staff->assignRole('staff');

    return $staff;
}

// =========================================================
// User Model — Staff Role & Relationships
// =========================================================

it('identifies staff users via isStaff()', function () {
    $staff = User::factory()->staff()->create();

    expect($staff->isStaff())->toBeTrue();
    expect($staff->isStoreOwner())->toBeFalse();
    expect($staff->isCustomer())->toBeFalse();
    expect($staff->isAdmin())->toBeFalse();
});

it('associates staff with a store via assignedStore relationship', function () {
    [, $store] = createOwnerWithStore();
    $staff = createStaffForStore($store);

    expect($staff->assignedStore->id)->toBe($store->id);
    expect($staff->store_id)->toBe($store->id);
});

it('returns assigned store via getStoreForPanel for staff', function () {
    [, $store] = createOwnerWithStore();
    $staff = createStaffForStore($store);

    expect($staff->getStoreForPanel()->id)->toBe($store->id);
});

it('returns owned store via getStoreForPanel for store owners', function () {
    [$owner, $store] = createOwnerWithStore();

    expect($owner->getStoreForPanel()->id)->toBe($store->id);
});

it('lists staff via store staffMembers relationship', function () {
    [, $store] = createOwnerWithStore();
    $staff1 = createStaffForStore($store);
    $staff2 = createStaffForStore($store);

    expect($store->staffMembers)->toHaveCount(2);
    expect($store->staffMembers->pluck('id')->toArray())
        ->toContain($staff1->id, $staff2->id);
});

// =========================================================
// Lunar Panel Access — Staff
// =========================================================

it('allows staff of approved stores to access the Lunar panel', function () {
    [, $store] = createOwnerWithStore();
    $staff = createStaffForStore($store);

    $this->actingAs($staff)
        ->get('/store/dashboard/tk_'.config('app.store_path_token'))
        ->assertOk();
});

it('blocks staff of pending stores from the Lunar panel', function () {
    $owner = User::factory()->storeOwner()->create();
    $store = Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Pending,
    ]);
    $staff = createStaffForStore($store);

    $this->actingAs($staff)
        ->get('/store/dashboard/tk_'.config('app.store_path_token'))
        ->assertForbidden();
});

it('blocks staff of suspended stores from the Lunar panel', function () {
    $owner = User::factory()->storeOwner()->create();
    $store = Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Suspended,
    ]);
    $staff = createStaffForStore($store);

    $this->actingAs($staff)
        ->get('/store/dashboard/tk_'.config('app.store_path_token'))
        ->assertForbidden();
});

it('blocks staff without a store_id from the Lunar panel', function () {
    $staff = User::factory()->staff()->create(['store_id' => null]);
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->get('/store/dashboard/tk_'.config('app.store_path_token'))
        ->assertForbidden();
});

// =========================================================
// Subdomain Login — Staff
// =========================================================

it('allows staff to login via their store subdomain', function () {
    [, $store] = createOwnerWithStore('staff-store');
    $staff = createStaffForStore($store);

    app()->instance('currentStore', $store);

    Livewire::withoutLazyLoading()
        ->test(StoreLogin::class, ['token' => $store->login_token])
        ->set('email', $staff->email)
        ->set('password', 'password')
        ->call('authenticate');

    $this->assertAuthenticatedAs($staff);
});

it('rejects staff login on a different store subdomain', function () {
    [, $store1] = createOwnerWithStore('store-one');
    $staff = createStaffForStore($store1);

    // Create a second store
    $owner2 = User::factory()->storeOwner()->create();
    $store2 = Store::factory()->for($owner2, 'owner')->create([
        'slug' => 'store-two',
        'status' => StoreStatus::Approved,
        'sector' => 'ecommerce',
    ]);

    // Try to login on store2's subdomain with staff from store1
    app()->instance('currentStore', $store2);

    Livewire::withoutLazyLoading()
        ->test(StoreLogin::class, ['token' => $store2->login_token])
        ->set('email', $staff->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('rejects staff login with wrong password', function () {
    [, $store] = createOwnerWithStore('staff-store-wrong');
    $staff = createStaffForStore($store);

    app()->instance('currentStore', $store);

    Livewire::withoutLazyLoading()
        ->test(StoreLogin::class, ['token' => $store->login_token])
        ->set('email', $staff->email)
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors('email');

    $this->assertGuest();
});

// =========================================================
// Permissions
// =========================================================

it('grants staff limited Lunar permissions', function () {
    [, $store] = createOwnerWithStore();
    $staff = createStaffForStore($store);

    expect($staff->hasPermissionTo('catalog:manage-products'))->toBeTrue();
    expect($staff->hasPermissionTo('catalog:manage-collections'))->toBeTrue();
    expect($staff->hasPermissionTo('sales:manage-orders'))->toBeTrue();
    expect($staff->hasPermissionTo('sales:manage-customers'))->toBeTrue();

    expect($staff->hasPermissionTo('settings'))->toBeFalse();
    expect($staff->hasPermissionTo('settings:core'))->toBeFalse();
    expect($staff->hasPermissionTo('settings:manage-staff'))->toBeFalse();
    expect($staff->hasPermissionTo('settings:manage-attributes'))->toBeFalse();
    expect($staff->hasPermissionTo('sales:manage-discounts'))->toBeFalse();
});

it('grants store owners all permissions', function () {
    [$owner] = createOwnerWithStore();

    expect($owner->hasPermissionTo('catalog:manage-products'))->toBeTrue();
    expect($owner->hasPermissionTo('settings'))->toBeTrue();
    expect($owner->hasPermissionTo('settings:manage-staff'))->toBeTrue();
    expect($owner->hasPermissionTo('sales:manage-discounts'))->toBeTrue();
});

// =========================================================
// Admin Attribute — Staff
// =========================================================

it('returns false admin attribute for staff users', function () {
    $staff = User::factory()->staff()->create();

    expect($staff->admin)->toBeFalse();
});
