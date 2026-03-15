<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PlatformRolesAndPermissionsSeeder extends Seeder
{
    /**
     * Platform-level permissions for the admin panel.
     *
     * @var list<string>
     */
    private const PERMISSIONS = [
        'manage-users',
        'manage-marketing',
        'manage-legal',
        'manage-platform',
        'manage-marketplace',
        'manage-tickets',
        'manage-ecommerce',
        'manage-content',
    ];

    /**
     * Roles and which permissions they receive.
     *
     * @var array<string, list<string>>
     */
    private const ROLE_MAP = [
        'super_admin' => '*',         // all permissions
        'manager' => [
            'manage-users',
            'manage-marketing',
            'manage-marketplace',
            'manage-tickets',
            'manage-ecommerce',
            'manage-content',
        ],
        'support' => [
            'manage-tickets',
            'manage-content',
        ],
        'moderator' => [
            'manage-content',
            'manage-marketplace',
            'manage-tickets',
        ],
        'finance' => [
            'manage-ecommerce',
            'manage-marketplace',
            'approve-legal',
        ],
    ];

    public function run(): void
    {
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        // Create permissions
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        $allPermissions = Permission::where('guard_name', $guard)
            ->whereIn('name', self::PERMISSIONS)
            ->get();

        // Create roles and assign permissions
        foreach (self::ROLE_MAP as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guard,
            ]);

            if ($permissions === '*') {
                $role->syncPermissions($allPermissions);
            } else {
                $rolePermissions = $allPermissions->filter(
                    fn (Permission $p) => in_array($p->name, $permissions)
                );
                $role->syncPermissions($rolePermissions);
            }
        }
    }
}
