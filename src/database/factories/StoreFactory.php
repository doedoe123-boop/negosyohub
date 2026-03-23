<?php

namespace Database\Factories;

use App\Models\Sector;
use App\Models\Store;
use App\Models\User;
use App\PayoutMethod;
use App\StoreStatus;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => UserRole::StoreOwner]),
            'name' => fake()->company().' PH',
            'slug' => fake()->unique()->slug(),
            'login_token' => 'stk_'.Str::random(24),
            'description' => fake()->paragraph(),
            'tagline' => fake()->catchPhrase(),
            'phone' => '+63 2 '.fake()->numerify('#### ####'),
            'website' => fake()->optional(0.7)->url(),
            'commission_rate' => 15.00,
            'status' => StoreStatus::Approved,
            'sector' => fn () => Sector::inRandomOrder()->value('slug') ?? 'ecommerce',
            'address' => [
                'line_one' => fake()->streetAddress(),
                'city' => fake()->randomElement(['Makati City', 'Quezon City', 'Pasig City', 'Cebu City', 'Davao City']),
                'postcode' => fake()->postcode(),
            ],
            'operating_hours' => [
                'monday' => ['open' => '09:00', 'close' => '18:00'],
                'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                'wednesday' => ['open' => '09:00', 'close' => '18:00'],
                'thursday' => ['open' => '09:00', 'close' => '18:00'],
                'friday' => ['open' => '09:00', 'close' => '18:00'],
                'saturday' => ['open' => '10:00', 'close' => '17:00'],
                'sunday' => null,
            ],
            'setup_completed_at' => now(),
        ];
    }

    /**
     * Indicate the store is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StoreStatus::Pending,
            'login_token' => null,
        ]);
    }

    /**
     * Indicate the store is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StoreStatus::Suspended,
            'suspended_at' => now(),
            'suspension_reason' => 'Terms violation',
        ]);
    }

    /**
     * Set a specific sector slug for the store.
     */
    public function sector(string $sectorSlug): static
    {
        return $this->state(fn (array $attributes) => [
            'sector' => $sectorSlug,
        ]);
    }

    public function ecommerce(): static
    {
        return $this->sector('ecommerce');
    }

    public function realEstate(): static
    {
        return $this->sector('real_estate')->state(fn (): array => [
            'agent_bio' => fake()->sentence(18),
            'agent_specializations' => fake()->randomElements(['Condos', 'House & Lot', 'Commercial', 'Pre-Selling'], 2),
            'prc_license_number' => 'PRC-REB-'.fake()->numerify('######'),
        ]);
    }

    public function rental(): static
    {
        return $this->sector('paupahan')->state(fn (): array => [
            'tagline' => 'Verified rental homes for long-term tenants.',
        ]);
    }

    public function movingService(): static
    {
        return $this->sector('lipat_bahay')->state(fn (): array => [
            'tagline' => 'Safe, scheduled, and professional household relocation.',
        ]);
    }

    /**
     * Set payout information for the store.
     */
    public function withPayout(PayoutMethod $method = PayoutMethod::GCash): static
    {
        return $this->state(fn (array $attributes) => match ($method) {
            PayoutMethod::BankTransfer => [
                'payout_method' => $method,
                'payout_details' => [
                    'account_name' => fake()->name(),
                    'account_number' => fake()->numerify('##########'),
                    'bank_name' => fake()->randomElement(['BDO', 'BPI', 'Metrobank', 'UnionBank', 'RCBC']),
                ],
            ],
            default => [
                'payout_method' => $method,
                'payout_details' => [
                    'account_name' => fake()->name(),
                    'mobile_number' => '09'.fake()->numerify('#########'),
                ],
            ],
        });
    }
}
