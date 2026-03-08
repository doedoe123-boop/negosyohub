<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use App\StoreStatus;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Creates 10 approved e-commerce stores and 10 approved real-estate stores
 * with realistic Philippine business data loaded from data/stores.php.
 *
 * Slugs are fixed so ProductSeeder and PropertyListingSeeder can look them up.
 */
class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $stores = require __DIR__.'/data/stores.php';

        foreach ($stores['ecommerce'] as $data) {
            $this->createStore($data, 'ecommerce');
        }

        foreach ($stores['real_estate'] as $data) {
            $this->createStore($data, 'real_estate', realEstate: true);
        }
    }

    /** @param array<string, mixed> $data */
    private function createStore(array $data, string $sector, bool $realEstate = false): void
    {
        $owner = User::factory()->create([
            'name' => fake()->name(),
            'email' => $data['email'],
            'role' => UserRole::StoreOwner,
        ]);
        $owner->assignRole('store_owner');

        $storeData = [
            'user_id' => $owner->id,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'login_token' => 'stk_'.Str::random(24),
            'tagline' => $data['tagline'] ?? null,
            'description' => $data['description'],
            'phone' => $data['phone'],
            'website' => $data['website'] ?? null,
            'commission_rate' => 12.00,
            'status' => StoreStatus::Approved,
            'sector' => $sector,
            'address' => $data['address'],
        ];

        if ($realEstate) {
            $storeData = array_merge($storeData, [
                'agent_bio' => $data['agent_bio'] ?? null,
                'prc_license_number' => $data['prc_license_number'] ?? null,
                'agent_specializations' => $data['agent_specializations'] ?? null,
                'default_interest_rate' => $data['default_interest_rate'] ?? 7.0,
                'default_loan_term_years' => $data['default_loan_term_years'] ?? 20,
                'default_down_payment_percent' => $data['default_down_payment_percent'] ?? 20,
            ]);
        }

        Store::create($storeData);
    }
}
