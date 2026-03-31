<?php

use App\Support\PermissionBootstrapper;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('seeds the ecommerce store panel permissions needed on production', function () {
    $this->artisan('permissions:seed')
        ->expectsOutputToContain('catalog:manage-products')
        ->expectsOutputToContain('sales:manage-discounts')
        ->assertSuccessful();

    foreach (PermissionBootstrapper::STORE_OWNER_PANEL_PERMISSIONS as $permission) {
        expect(Permission::query()->where('guard_name', 'web')->where('name', $permission)->exists())
            ->toBeTrue();
    }
});

it('assigns the full ecommerce panel permissions to store owners', function () {
    $this->artisan('permissions:seed')->assertSuccessful();

    $storeOwnerRole = Role::findByName('store_owner', 'web');

    foreach (PermissionBootstrapper::STORE_OWNER_PANEL_PERMISSIONS as $permission) {
        expect($storeOwnerRole->hasPermissionTo($permission))->toBeTrue();
    }

    expect($storeOwnerRole->hasPermissionTo('stores.view'))->toBeTrue()
        ->and($storeOwnerRole->hasPermissionTo('payouts.view'))->toBeTrue();
});

it('keeps staff limited to the intended ecommerce subset', function () {
    $this->artisan('permissions:seed')->assertSuccessful();

    $staffRole = Role::findByName('staff', 'web');

    foreach (PermissionBootstrapper::STAFF_PANEL_PERMISSIONS as $permission) {
        expect($staffRole->hasPermissionTo($permission))->toBeTrue();
    }

    expect($staffRole->hasPermissionTo('settings'))->toBeFalse()
        ->and($staffRole->hasPermissionTo('settings:manage-staff'))->toBeFalse()
        ->and($staffRole->hasPermissionTo('sales:manage-discounts'))->toBeFalse();
});
