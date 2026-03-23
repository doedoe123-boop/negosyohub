<?php

namespace Database\Seeders;

use App\Models\Development;
use App\Models\Property;
use App\Models\Store;
use App\Support\MediaSeederHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        Store::query()
            ->where('sector', 'real_estate')
            ->get()
            ->each(function (Store $store): void {
                $development = Development::query()->firstOrCreate(
                    [
                        'store_id' => $store->id,
                        'name' => $store->name.' Signature Residences',
                    ],
                    [
                        'slug' => Str::slug($store->name.' signature residences'),
                        'description' => "{$store->name} Signature Residences is a master-planned residential project near {$store->address['city']} offering curated amenities, secure access, and professionally managed common areas.",
                        'developer_name' => $store->name,
                        'development_type' => 'condominium',
                        'status' => 'active',
                        'address_line' => $store->address['line1'] ?? null,
                        'barangay' => $store->address['barangay'] ?? null,
                        'city' => $store->address['city'] ?? null,
                        'province' => $store->address['province'] ?? 'Metro Manila',
                        'zip_code' => $store->address['postal_code'] ?? null,
                        'latitude' => 14.5547,
                        'longitude' => 121.0244,
                        'total_units' => 240,
                        'available_units' => 18,
                        'floors' => 32,
                        'year_built' => 2024,
                        'price_range_min' => 4200000,
                        'price_range_max' => 12800000,
                        'amenities' => ['Swimming Pool', 'Gym', 'Function Room', 'Sky Lounge', 'Retail Podium'],
                        'website_url' => $store->website,
                        'is_featured' => true,
                        'published_at' => now(),
                    ],
                );

                MediaSeederHelper::attachImages($development, 'condo', 'images', 4);
                MediaSeederHelper::attachImages($development, 'condo', 'logo', 1);

                Property::query()
                    ->where('store_id', $store->id)
                    ->whereNull('development_id')
                    ->whereIn('property_type', ['condo', 'apartment'])
                    ->limit(2)
                    ->get()
                    ->each(function (Property $property) use ($development): void {
                        $property->updateQuietly([
                            'development_id' => $development->id,
                        ]);
                    });

                $development->syncAvailableUnits();
            });
    }
}
