<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PermissionBootstrapper;
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

        $this->call(PlatformRolesAndPermissionsSeeder::class);

        $this->call([
            SectorSeeder::class,
            UserSeeder::class,
            StoreSeeder::class,
            EcommerceSeeder::class,
            RealtySeeder::class,
            RentalSeeder::class,
            MovingServiceSeeder::class,
            LogisticsSeeder::class,
            PayoutSeeder::class,
            LegalPageSeeder::class,
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
        $staffGuard = 'staff';

        (new PermissionBootstrapper)->seedWebRolesAndPermissions();

        $webPermissions = Permission::where('guard_name', 'web')->get();

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
