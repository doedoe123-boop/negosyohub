<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\PaymentMethod;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'Password123!';

    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@negosyohub.test'],
            [
                'name' => 'NegosyoHub Admin',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
                'phone' => '09170000001',
                'role' => UserRole::Admin,
            ],
        );
        $admin->assignRole(['admin', 'super_admin']);

        $customer = User::query()->updateOrCreate(
            ['email' => 'customer@negosyohub.test'],
            [
                'name' => 'Demo Customer',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
                'phone' => '09170000002',
                'role' => UserRole::Customer,
            ],
        );

        $tenant = User::query()->updateOrCreate(
            ['email' => 'tenant@negosyohub.test'],
            [
                'name' => 'Demo Tenant',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
                'phone' => '09170000003',
                'role' => UserRole::Customer,
            ],
        );

        Address::query()->firstOrCreate(
            ['user_id' => $customer->id, 'label' => 'Home'],
            Address::factory()->default()->make([
                'user_id' => $customer->id,
                'label' => 'Home',
                'line1' => '123 Pioneer Street',
                'city' => 'Mandaluyong City',
                'province' => 'Metro Manila',
                'postal_code' => '1550',
            ])->toArray(),
        );

        Address::query()->firstOrCreate(
            ['user_id' => $tenant->id, 'label' => 'Home'],
            Address::factory()->default()->make([
                'user_id' => $tenant->id,
                'label' => 'Home',
                'line1' => '88 Lahug Avenue',
                'city' => 'Cebu City',
                'province' => 'Cebu',
                'postal_code' => '6000',
            ])->toArray(),
        );

        PaymentMethod::query()->firstOrCreate(
            ['user_id' => $customer->id, 'paymongo_id' => 'pm_demo_customer_default'],
            PaymentMethod::factory()->default()->make([
                'user_id' => $customer->id,
                'paymongo_id' => 'pm_demo_customer_default',
                'paymongo_customer_id' => 'cus_demo_customer',
                'brand' => 'Visa',
                'last4' => '4242',
            ])->toArray(),
        );
    }
}
