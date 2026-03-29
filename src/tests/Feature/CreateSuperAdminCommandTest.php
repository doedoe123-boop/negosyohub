<?php

use App\Models\User;
use App\UserRole;
use Database\Seeders\PlatformRolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->artisan('permissions:seed')->assertSuccessful();
    $this->seed(PlatformRolesAndPermissionsSeeder::class);
});

it('creates a super admin and ensures required roles exist', function () {
    $this->artisan('app:create-super-admin', [
        'email' => 'ops-admin@example.com',
        '--name' => 'Ops Admin',
        '--password' => 'Str0ng#Admin9',
    ])
        ->expectsOutputToContain('Super admin is ready.')
        ->expectsOutputToContain('ops-admin@example.com')
        ->assertSuccessful();

    $user = User::query()->where('email', 'ops-admin@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Ops Admin')
        ->and($user->role)->toBe(UserRole::Admin)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->hasRole('admin'))->toBeTrue()
        ->and($user->hasRole('super_admin'))->toBeTrue()
        ->and(Role::query()->where('guard_name', 'web')->where('name', 'super_admin')->exists())->toBeTrue();
});

it('updates an existing admin user when rerun', function () {
    $existing = User::factory()->create([
        'email' => 'ops-admin@example.com',
        'name' => 'Old Name',
        'role' => UserRole::Customer,
        'email_verified_at' => null,
    ]);

    $this->artisan('app:create-super-admin', [
        'email' => 'ops-admin@example.com',
        '--name' => 'Updated Admin',
        '--password' => 'An0ther#Pass9',
    ])->assertSuccessful();

    $existing->refresh();

    expect($existing->name)->toBe('Updated Admin')
        ->and($existing->role)->toBe(UserRole::Admin)
        ->and($existing->email_verified_at)->not->toBeNull()
        ->and($existing->hasRole('admin'))->toBeTrue()
        ->and($existing->hasRole('super_admin'))->toBeTrue();
});
