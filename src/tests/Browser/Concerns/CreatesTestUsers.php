<?php

namespace Tests\Browser\Concerns;

use App\Models\Store;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Shared helper methods for Dusk browser tests.
 *
 * Provides convenient factory shortcuts for creating
 * authenticated users across all three Filament panels.
 */
trait CreatesTestUsers
{
    /**
     * Create an admin user ready for the Admin panel.
     */
    protected function createAdmin(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * Create a store owner with an approved (non-realty) store.
     *
     * Assigns the Lunar 'staff' role so the owner can access
     * Lunar panel resources (products, orders, etc.).
     *
     * @return array{user: User, store: Store}
     */
    protected function createStoreOwner(): array
    {
        $store = Store::factory()
            ->sector('ecommerce')
            ->create();

        $user = $store->owner;

        $this->grantLunarStaffRole($user);

        return ['user' => $user, 'store' => $store];
    }

    /**
     * Create a store owner with an approved real-estate store.
     *
     * @return array{user: User, store: Store}
     */
    protected function createRealtyAgent(): array
    {
        $store = Store::factory()
            ->sector('real_estate')
            ->create();

        $user = $store->owner;

        return ['user' => $user, 'store' => $store];
    }

    /**
     * Assign the Lunar 'staff' role so the user can access panel resources.
     */
    protected function grantLunarStaffRole(User $user): void
    {
        $guard = config('auth.defaults.guard', 'web');

        $role = Role::firstOrCreate([
            'name' => 'staff',
            'guard_name' => $guard,
        ]);

        $lunarPermissions = [
            'catalog:manage-products',
            'catalog:manage-collections',
            'sales:manage-orders',
            'sales:manage-customers',
            'settings',
        ];

        foreach ($lunarPermissions as $handle) {
            $permission = \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $handle,
                'guard_name' => $guard,
            ]);
            $role->givePermissionTo($permission);
        }

        $user->assignRole($role);
    }

    /**
     * Return the Admin panel base path.
     */
    protected function adminPath(): string
    {
        return '/moon/portal/itsec_tk_'.config('app.admin_path_token');
    }

    /**
     * Return the Store (Lunar) panel base path.
     */
    protected function storePath(): string
    {
        return '/store/dashboard/tk_'.config('app.store_path_token');
    }

    /**
     * Return the Realty panel base path.
     */
    protected function realtyPath(): string
    {
        return '/realty/dashboard/tk_'.config('app.realty_path_token');
    }
}
