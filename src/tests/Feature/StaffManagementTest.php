<?php

use App\Models\User;
use App\UserRole;
use Database\Seeders\PlatformRolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PlatformRolesAndPermissionsSeeder::class);
});

it('seeds platform roles and permissions', function () {
    expect(Role::where('guard_name', 'web')->whereIn('name', [
        'super_admin', 'manager', 'support', 'moderator', 'finance',
    ])->count())->toBe(5);
});

it('allows assigning spatie roles to an admin user', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $user->assignRole('manager');

    expect($user->hasRole('manager'))->toBeTrue();
});

it('assigns multiple roles to a user', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $user->assignRole(['super_admin', 'manager']);

    expect($user->hasRole('super_admin'))->toBeTrue()
        ->and($user->hasRole('manager'))->toBeTrue()
        ->and($user->getRoleNames()->count())->toBe(2);
});

it('super_admin has all platform permissions', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $user->assignRole('super_admin');

    expect($user->hasPermissionTo('manage-users'))->toBeTrue()
        ->and($user->hasPermissionTo('manage-tickets'))->toBeTrue()
        ->and($user->hasPermissionTo('manage-platform'))->toBeTrue();
});

it('support role only has limited permissions', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $user->assignRole('support');

    expect($user->hasPermissionTo('manage-tickets'))->toBeTrue()
        ->and($user->hasPermissionTo('manage-content'))->toBeTrue()
        ->and($user->hasPermissionTo('manage-users'))->toBeFalse()
        ->and($user->hasPermissionTo('manage-platform'))->toBeFalse();
});

it('finance role has correct permissions', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $user->assignRole('finance');

    expect($user->hasPermissionTo('manage-ecommerce'))->toBeTrue()
        ->and($user->hasPermissionTo('manage-marketplace'))->toBeTrue()
        ->and($user->hasPermissionTo('manage-users'))->toBeFalse();
});

it('can deactivate and reactivate a staff member via soft delete', function () {
    $staff = User::factory()->create(['role' => UserRole::Admin]);

    // Deactivate
    $staff->delete();
    expect($staff->trashed())->toBeTrue();

    // Reactivate
    $staff->restore();
    expect($staff->trashed())->toBeFalse();
});

it('logs activity when a staff user is created', function () {
    $staff = User::factory()->create([
        'role' => UserRole::Admin,
        'name' => 'Test Staff',
    ]);

    // The LogsActivity trait auto-logs 'created' on model creation
    $latestLog = $staff->activities()->latest()->first();

    expect($latestLog)->not->toBeNull()
        ->and($latestLog->description)->toBe('created');
});

it('has latestLogin relationship', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    // Create login history entries
    $user->loginHistory()->create([
        'email' => $user->email,
        'ip_address' => '127.0.0.1',
        'status' => 'success',
    ]);

    $user->loginHistory()->create([
        'email' => $user->email,
        'ip_address' => '10.0.0.1',
        'status' => 'success',
    ]);

    $latestLogin = $user->latestLogin;

    expect($latestLogin)->not->toBeNull()
        ->and($latestLogin->ip_address)->toBe('10.0.0.1');
});
