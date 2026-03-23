<?php

namespace Database\Seeders;

use App\InquiryStatus;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\RentalAgreement;
use App\Models\Store;
use App\Models\User;
use App\PropertyStatus;
use App\Support\MediaSeederHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RentalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catalog = require __DIR__.'/data/rentals.php';

        $tenant = User::query()->where('email', 'tenant@negosyohub.test')->firstOrFail();

        foreach ($catalog as $storeSlug => $listings) {
            $store = Store::query()->where('slug', $storeSlug)->first();

            if (! $store) {
                $this->command?->warn("RentalSeeder: store [{$storeSlug}] not found — skipping.");

                continue;
            }

            $properties = collect();

            foreach ($listings as $index => $listing) {
                $property = Property::query()->updateOrCreate(
                    ['slug' => "{$store->slug}-rental-".Str::slug($listing['title'])],
                    array_merge($listing, [
                        'store_id' => $store->id,
                        'published_at' => now(),
                    ]),
                );

                MediaSeederHelper::attachImages(
                    $property,
                    MediaSeederHelper::keywordForPropertyType($listing['property_type'] ?? 'apartment'),
                    'images',
                    4
                );

                $properties->push($property);

                if ($index === 0) {
                    $this->seedInquiryFor($property, $tenant);
                }
            }

            $availableProperty = $properties->first();
            if ($availableProperty) {
                $this->seedPendingAgreement($availableProperty, $store, $tenant);
            }

            $signedProperty = $properties->skip(1)->first() ?? $availableProperty;
            if ($signedProperty) {
                $this->seedSignedAgreement($signedProperty, $store, $tenant);
            }
        }
    }

    private function seedInquiryFor(Property $property, User $tenant): void
    {
        PropertyInquiry::query()->updateOrCreate(
            [
                'property_id' => $property->id,
                'email' => $tenant->email,
            ],
            [
                'store_id' => $property->store_id,
                'user_id' => $tenant->id,
                'name' => $tenant->name,
                'phone' => $tenant->phone ?? '09171234567',
                'message' => 'Interested in moving in next month. Is the unit still available, and are pets allowed?',
                'status' => InquiryStatus::ViewingScheduled,
                'contacted_at' => now()->subDays(2),
                'viewing_date' => now()->addDays(4),
                'source' => 'website',
            ]
        );
    }

    private function seedPendingAgreement(Property $property, Store $store, User $tenant): void
    {
        RentalAgreement::query()->updateOrCreate(
            [
                'property_id' => $property->id,
                'tenant_email' => $tenant->email,
            ],
            [
                'store_id' => $store->id,
                'tenant_user_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'tenant_phone' => $tenant->phone ?? '09171234567',
                'monthly_rent' => (int) round((float) $property->price * 100),
                'security_deposit' => (int) round((float) $property->price * 200),
                'move_in_date' => now()->addWeeks(3)->toDateString(),
                'lease_term_months' => 12,
                'notes' => 'Demo pending agreement for walkthrough and document review.',
                'status' => 'negotiating',
                'tenant_questions' => 'Can the landlord include one parking slot and allow a small indoor cat?',
                'landlord_response' => 'One parking slot is included. Pets are allowed subject to building rules.',
                'signed_at' => null,
            ]
        );
    }

    private function seedSignedAgreement(Property $property, Store $store, User $tenant): void
    {
        $agreement = RentalAgreement::query()->updateOrCreate(
            [
                'property_id' => $property->id,
                'tenant_email' => 'tenant@negosyohub.test',
            ],
            [
                'store_id' => $store->id,
                'tenant_user_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'tenant_phone' => $tenant->phone ?? '09171234567',
                'monthly_rent' => (int) round((float) $property->price * 100),
                'security_deposit' => (int) round((float) $property->price * 200),
                'move_in_date' => now()->addWeeks(2)->toDateString(),
                'lease_term_months' => 12,
                'notes' => 'Demo signed agreement for a move-in-ready tenant.',
                'status' => 'signed',
                'tenant_questions' => null,
                'landlord_response' => null,
                'signed_at' => now()->subDays(5),
            ]
        );

        $property->updateQuietly(['status' => PropertyStatus::Rented]);
    }
}
