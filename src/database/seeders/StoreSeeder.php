<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use App\PayoutMethod;
use App\StoreStatus;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seed demo-ready stores and linked store-owner accounts across all sectors.
 */
class StoreSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'Password123!';

    public function run(): void
    {
        /** @var array{ecommerce: array<int, array<string, mixed>>, real_estate: array<int, array<string, mixed>>, rental: array<int, array<string, mixed>>, moving_service: array<int, array<string, mixed>>} $stores */
        $stores = require __DIR__.'/data/stores.php';

        foreach ($stores['ecommerce'] as $data) {
            $this->upsertStore($data, 'ecommerce');
        }

        foreach ($stores['real_estate'] as $data) {
            $this->upsertStore($data, 'real_estate');
        }

        foreach ($stores['rental'] as $data) {
            $this->upsertStore($data, 'paupahan');
        }

        foreach ($stores['moving_service'] as $data) {
            $this->upsertStore($data, 'lipat_bahay');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertStore(array $data, string $sector): void
    {
        $ownerName = $data['owner_name'] ?? Str::of($data['name'])->before(' ')->append(' Demo')->value();

        $owner = User::query()->updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $ownerName,
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
                'phone' => $data['phone'] ?? '09171234567',
                'role' => UserRole::StoreOwner,
            ],
        );

        if (! $owner->hasRole('store_owner')) {
            $owner->assignRole('store_owner');
        }

        Store::query()->updateOrCreate(
            ['slug' => $data['slug']],
            [
                'user_id' => $owner->id,
                'name' => $data['name'],
                'login_token' => 'stk_demo_'.$data['slug'],
                'tagline' => $data['tagline'] ?? null,
                'description' => $data['description'],
                'phone' => $data['phone'],
                'website' => $data['website'] ?? null,
                'logo' => $data['logo'] ?? null,
                'banner' => $data['banner'] ?? $this->bannerForSector($sector),
                'commission_rate' => 12.00,
                'status' => StoreStatus::Approved,
                'sector' => $sector,
                'address' => $data['address'],
                'agent_bio' => $data['agent_bio'] ?? null,
                'agent_photo' => $data['agent_photo'] ?? null,
                'prc_license_number' => $data['prc_license_number'] ?? null,
                'agent_specializations' => $data['agent_specializations'] ?? null,
                'default_interest_rate' => $data['default_interest_rate'] ?? null,
                'default_loan_term_years' => $data['default_loan_term_years'] ?? null,
                'default_down_payment_percent' => $data['default_down_payment_percent'] ?? null,
                'operating_hours' => $data['operating_hours'] ?? $this->defaultOperatingHours(),
                'payout_method' => $data['payout_method'] ?? PayoutMethod::GCash,
                'payout_details' => $data['payout_details'] ?? [
                    'account_name' => $ownerName,
                    'mobile_number' => '09'.fake()->numerify('#########'),
                ],
                'setup_completed_at' => now(),
            ],
        );
    }

    /**
     * @return array<string, array{open: string, close: string}|null>
     */
    private function defaultOperatingHours(): array
    {
        return [
            'monday' => ['open' => '09:00', 'close' => '18:00'],
            'tuesday' => ['open' => '09:00', 'close' => '18:00'],
            'wednesday' => ['open' => '09:00', 'close' => '18:00'],
            'thursday' => ['open' => '09:00', 'close' => '18:00'],
            'friday' => ['open' => '09:00', 'close' => '18:00'],
            'saturday' => ['open' => '10:00', 'close' => '16:00'],
            'sunday' => null,
        ];
    }

    private function bannerForSector(string $sector): string
    {
        return match ($sector) {
            'ecommerce' => 'https://images.unsplash.com/photo-1556740749-887f6717d7e4?w=1400&auto=format&fit=crop&q=80',
            'real_estate' => 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=1400&auto=format&fit=crop&q=80',
            'paupahan' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=1400&auto=format&fit=crop&q=80',
            'lipat_bahay' => 'https://images.unsplash.com/photo-1600518464441-9154a4dea21b?w=1400&auto=format&fit=crop&q=80',
            default => 'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?w=1400&auto=format&fit=crop&q=80',
        };
    }
}
