<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(LunarSeeder::class);

        $this->seedRolesAndPermissions();

        // Platform-level roles & permissions (admin panel staff management)
        $this->call(PlatformRolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@marketplace.test',
            'role' => UserRole::Admin,
        ]);
        $admin->assignRole(['admin', 'super_admin']);

        User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'role' => UserRole::Customer,
        ]);

        $this->call([
            StoreSeeder::class,
            PayoutSeeder::class,
            LunarCatalogSeeder::class,
            LegalPageSeeder::class,
            SectorSeeder::class,
            ProductSeeder::class,
            PropertyListingSeeder::class,
            ShippingSeeder::class,
            CategorySeeder::class,
            FaqSeeder::class,
        ]);

        $this->assignStoreOwnerRoles();
    }

    /**
     * Configure Spatie roles with Lunar permissions.
     *
     * Roles and permissions are created for the 'web' guard (used by our User model)
     * AND the 'staff' guard (used by Lunar's Staff model internally).
     */
    private function seedRolesAndPermissions(): void
    {
        $webGuard = 'web';
        $staffGuard = 'staff';

        // Web guard roles (for User model — store owners, admins, staff)
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $webGuard]);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => $webGuard]);
        $storeOwnerRole = Role::firstOrCreate(['name' => 'store_owner', 'guard_name' => $webGuard]);

        $webPermissions = Permission::where('guard_name', $webGuard)->get();
        $adminRole->syncPermissions($webPermissions);
        $storeOwnerRole->syncPermissions($webPermissions);

        // Staff get limited permissions (no settings, no staff management)
        $staffPermissions = $webPermissions->filter(
            fn (Permission $p) => in_array($p->name, self::STAFF_PERMISSIONS)
        );
        $staffRole->syncPermissions($staffPermissions);

        // Lunar's internal staff guard (for Staff model compatibility)
        $staffAdminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $staffGuard]);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => $staffGuard]);

        foreach ($webPermissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm->name,
                'guard_name' => $staffGuard,
            ]);
        }

        $lunarStaffPermissions = Permission::where('guard_name', $staffGuard)->get();
        $staffAdminRole->syncPermissions($lunarStaffPermissions);
    }

    /**
     * Permissions granted to store staff members.
     *
     * @var list<string>
     */
    private const STAFF_PERMISSIONS = [
        'catalog:manage-products',
        'catalog:manage-collections',
        'sales:manage-orders',
        'sales:manage-customers',
    ];

    /**
     * Assign the store_owner Spatie role to all seeded store owners.
     */
    private function assignStoreOwnerRoles(): void
    {
        User::where('role', UserRole::StoreOwner)->each(function (User $user): void {
            if (! $user->hasRole('store_owner')) {
                $user->assignRole('store_owner');
            }
        });
    }
}
