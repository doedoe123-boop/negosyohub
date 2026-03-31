<?php

namespace App\Console\Commands;

use App\Support\PermissionBootstrapper;
use Illuminate\Console\Command;

/**
 * Seed Spatie permissions and assign them to roles.
 *
 * This is idempotent — safe to run multiple times.
 * Existing permissions/roles won't be duplicated.
 */
class SeedPermissionsCommand extends Command
{
    protected $signature = 'permissions:seed';

    protected $description = 'Seed fine-grained permissions and assign to roles';

    public function handle(): int
    {
        $this->info('Seeding permissions...');

        $permissionBootstrapper = new PermissionBootstrapper;
        $allPermissions = $permissionBootstrapper->seedWebRolesAndPermissions();

        foreach ($allPermissions as $permission) {
            $this->line("  ✓ {$permission}");
        }

        $this->newLine();
        $this->info('Assigning permissions to roles...');

        $this->line('  ✓ admin → all seeded web permissions');
        $this->line('  ✓ store_owner → store-scoped permissions');
        $this->line('  ✓ staff → limited permissions');
        $this->line('  ✓ customer → customer permissions');

        $this->newLine();
        $this->info('Done! '.count($allPermissions).' permissions seeded across 4 roles.');

        return self::SUCCESS;
    }
}
