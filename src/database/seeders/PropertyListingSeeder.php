<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds property listings for all 10 real-estate stores.
 *
 * Data is kept in data/properties.php (keyed by store slug).
 *
 * Depends on: StoreSeeder (must run first).
 */
class PropertyListingSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = require __DIR__.'/data/properties.php';

        foreach ($catalog as $slug => $listings) {
            $store = Store::where('slug', $slug)->first();

            if (! $store) {
                $this->command->getOutput()->writeln("<comment>PropertyListingSeeder: store [{$slug}] not found — skipping.</comment>");

                continue;
            }

            if (Property::where('store_id', $store->id)->exists()) {
                $this->command->getOutput()->writeln("<comment>PropertyListingSeeder: [{$slug}] already seeded — skipping.</comment>");

                continue;
            }

            foreach ($listings as $data) {
                Property::create(array_merge(
                    [
                        'store_id' => $store->id,
                        'slug' => Str::slug($data['title']).'-'.Str::random(6),
                        'published_at' => now(),
                    ],
                    $data,
                ));
            }
        }
    }
}
