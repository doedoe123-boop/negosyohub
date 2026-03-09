<?php

use App\Models\Store;
use App\Models\User;
use App\StoreStatus;
use App\UserRole;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Ensure the store_owner Spatie role exists with all permissions
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
 * Build the dynamic Lunar panel path.
 */
function lunarPath(): string
{
    return '/store/dashboard/tk_'.config('app.store_path_token');
}

// =========================================================
// Lunar Panel — Store Owners Only
// =========================================================

it('allows approved store owners to access the Lunar panel', function () {
    $owner = User::factory()->storeOwner()->create();
    $owner->assignRole('store_owner');
    Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Approved,
        'sector' => 'ecommerce',
        'setup_completed_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(lunarPath())
        ->assertOk();
});

it('redirects approved store owners with incomplete setup to the setup wizard', function () {
    $owner = User::factory()->storeOwner()->create();
    $owner->assignRole('store_owner');
    Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Approved,
        'sector' => 'ecommerce',
        'setup_completed_at' => null,
    ]);

    $this->actingAs($owner)
        ->get(lunarPath())
        ->assertRedirectContains('/setup');
});

it('blocks pending store owners from the Lunar panel', function () {
    $owner = User::factory()->storeOwner()->create();
    $owner->assignRole('store_owner');
    Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Pending,
    ]);

    $this->actingAs($owner)
        ->get(lunarPath())
        ->assertForbidden();
});

it('blocks suspended store owners from the Lunar panel', function () {
    $owner = User::factory()->storeOwner()->create();
    $owner->assignRole('store_owner');
    Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Suspended,
    ]);

    $this->actingAs($owner)
        ->get(lunarPath())
        ->assertForbidden();
});

it('blocks store owners without a store from the Lunar panel', function () {
    $owner = User::factory()->storeOwner()->create();

    $this->actingAs($owner)
        ->get(lunarPath())
        ->assertForbidden();
});

it('blocks admins from the Lunar panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(lunarPath())
        ->assertForbidden();
});

it('blocks customers from the Lunar panel', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)
        ->get(lunarPath())
        ->assertForbidden();
});

it('blocks guests from the Lunar panel', function () {
    $this->get(lunarPath())
        ->assertRedirect();
});

// =========================================================
// Admin Panel — Admins Only
// =========================================================

it('allows admins to access the admin panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(adminPath())
        ->assertOk();
});

it('blocks store owners from the admin panel', function () {
    $owner = User::factory()->storeOwner()->create();
    $owner->assignRole('store_owner');
    Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Approved,
        'sector' => 'ecommerce',
    ]);

    $this->actingAs($owner)
        ->get(adminPath())
        ->assertForbidden();
});

it('blocks customers from the admin panel', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)
        ->get(adminPath())
        ->assertForbidden();
});

it('blocks guests from the admin panel', function () {
    $this->get(adminPath())
        ->assertRedirect();
});

// =========================================================
// Store Dashboard Redirect
// =========================================================

it('redirects approved store owners from /store/dashboard to the Lunar panel', function () {
    $owner = User::factory()->storeOwner()->create();
    Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Approved,
        'sector' => 'ecommerce',
    ]);

    $this->actingAs($owner)
        ->get(route('store.dashboard'))
        ->assertRedirect(lunarPath());
});

it('shows pending status page for unapproved store owners', function () {
    $owner = User::factory()->storeOwner()->create();
    Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Pending,
    ]);

    $this->actingAs($owner)
        ->get(route('store.dashboard'))
        ->assertOk()
        ->assertSee('Pending');
});

// =========================================================
// Admin Attribute (for Lunar Gate::after)
// =========================================================

it('grants admin users the admin attribute for Lunar Gate', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->admin)->toBeTrue();
});

it('returns false admin attribute for non-admin users', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $storeOwner = User::factory()->storeOwner()->create();

    expect($customer->admin)->toBeFalse();
    expect($storeOwner->admin)->toBeFalse();
});

// =========================================================
// Navigation Links
// =========================================================

it('shows Admin Panel link for admins', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('home'))
        ->assertSee('Admin Panel');
});
