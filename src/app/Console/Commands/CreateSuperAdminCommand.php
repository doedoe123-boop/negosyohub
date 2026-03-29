<?php

namespace App\Console\Commands;

use App\Models\User;
use App\UserRole;
use Database\Seeders\PlatformRolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateSuperAdminCommand extends Command
{
    protected $signature = 'app:create-super-admin
        {email : The email address for the super admin}
        {--name= : The display name for the super admin}
        {--password= : The password to assign. If omitted, a secure password will be generated}
        {--unverified : Leave the email address unverified}';

    protected $description = 'Seed the required web roles and permissions, then create or update a production-ready super admin user';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $name = $this->option('name') ? (string) $this->option('name') : $this->defaultNameFor($email);
        $password = $this->option('password') ? (string) $this->option('password') : Str::password(20);
        $emailVerifiedAt = $this->option('unverified') ? null : now();

        $this->info('Seeding permissions and roles...');
        $this->call('permissions:seed');
        (new PlatformRolesAndPermissionsSeeder)->run();

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => UserRole::Admin,
            ],
        );

        $user->forceFill([
            'email_verified_at' => $emailVerifiedAt,
        ])->save();

        $user->assignRole(['admin', 'super_admin']);

        $this->newLine();
        $this->info('Super admin is ready.');
        $this->line("Email: {$user->email}");
        $this->line("Name: {$user->name}");
        $this->line('Roles: admin, super_admin');
        $this->line('Password: '.$password);

        if ($emailVerifiedAt === null) {
            $this->warn('Email was left unverified because --unverified was used.');
        }

        return self::SUCCESS;
    }

    private function defaultNameFor(string $email): string
    {
        $localPart = Str::before($email, '@');

        return (string) Str::of($localPart)
            ->replace(['.', '_', '-'], ' ')
            ->title();
    }
}
