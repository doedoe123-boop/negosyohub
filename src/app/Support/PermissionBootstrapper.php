<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionBootstrapper
{
    /**
     * Generic app permissions grouped by resource.
     *
     * @var array<string, list<string>>
     */
    private const GENERIC_PERMISSIONS = [
        'stores' => ['view', 'create', 'update', 'delete', 'approve', 'suspend'],
        'orders' => ['view', 'create', 'update', 'delete'],
        'payouts' => ['view', 'manage'],
        'users' => ['view', 'manage'],
        'announcements' => ['view', 'create', 'update', 'delete'],
        'legal-pages' => ['view', 'create', 'update', 'delete'],
        'support-tickets' => ['view', 'manage'],
    ];

    /**
     * Store-owner permissions required by the ecommerce/Lunar panel.
     *
     * @var list<string>
     */
    public const STORE_OWNER_PANEL_PERMISSIONS = [
        'settings',
        'settings:core',
        'settings:manage-staff',
        'settings:manage-attributes',
        'catalog:manage-products',
        'catalog:manage-collections',
        'sales:manage-orders',
        'sales:manage-customers',
        'sales:manage-discounts',
    ];

    /**
     * Limited store-staff permissions for the ecommerce/Lunar panel.
     *
     * @var list<string>
     */
    public const STAFF_PANEL_PERMISSIONS = [
        'catalog:manage-products',
        'catalog:manage-collections',
        'sales:manage-orders',
        'sales:manage-customers',
    ];

    /**
     * @return list<string>
     */
    public function seedWebRolesAndPermissions(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = [];

        foreach (self::GENERIC_PERMISSIONS as $resource => $actions) {
            foreach ($actions as $action) {
                $allPermissions[] = "{$resource}.{$action}";
            }
        }

        $allPermissions = array_values(array_unique([
            ...$allPermissions,
            ...self::STORE_OWNER_PANEL_PERMISSIONS,
        ]));

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])
            ->syncPermissions($allPermissions);

        Role::firstOrCreate(['name' => 'store_owner', 'guard_name' => 'web'])
            ->syncPermissions($this->storeOwnerPermissions());

        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web'])
            ->syncPermissions($this->staffPermissions());

        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web'])
            ->syncPermissions($this->customerPermissions());

        return $allPermissions;
    }

    /**
     * @return list<string>
     */
    public function storeOwnerPermissions(): array
    {
        return [
            'stores.view',
            'stores.update',
            'orders.view',
            'orders.create',
            'orders.update',
            'payouts.view',
            ...self::STORE_OWNER_PANEL_PERMISSIONS,
        ];
    }

    /**
     * @return list<string>
     */
    public function staffPermissions(): array
    {
        return [
            'stores.view',
            'orders.view',
            'orders.update',
            ...self::STAFF_PANEL_PERMISSIONS,
        ];
    }

    /**
     * @return list<string>
     */
    public function customerPermissions(): array
    {
        return [
            'orders.view',
            'orders.create',
        ];
    }
}
