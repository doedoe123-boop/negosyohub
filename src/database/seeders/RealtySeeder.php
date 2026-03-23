<?php

namespace Database\Seeders;

use App\Models\OpenHouse;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\Store;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class RealtySeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PropertyListingSeeder::class,
            DevelopmentSeeder::class,
        ]);

        Store::query()
            ->where('sector', 'real_estate')
            ->get()
            ->each(function (Store $store): void {
                $featuredProperty = Property::query()
                    ->where('store_id', $store->id)
                    ->where('status', 'active')
                    ->latest('is_featured')
                    ->first();

                if (! $featuredProperty) {
                    return;
                }

                Testimonial::query()->firstOrCreate(
                    [
                        'store_id' => $store->id,
                        'property_id' => $featuredProperty->id,
                        'client_name' => 'Demo Buyer',
                    ],
                    [
                        'title' => 'Smooth property viewing experience',
                        'content' => 'The listing details, follow-up communication, and site tour process all felt realistic and polished for a live demo.',
                        'rating' => 5,
                        'is_published' => true,
                    ],
                );

                OpenHouse::query()->firstOrCreate(
                    [
                        'property_id' => $featuredProperty->id,
                        'title' => 'Weekend Open House',
                    ],
                    [
                        'store_id' => $store->id,
                        'description' => 'Meet the agent at the lobby reception for a guided walkthrough of the property.',
                        'event_date' => now()->addDays(5)->toDateString(),
                        'start_time' => '14:00',
                        'end_time' => '17:00',
                        'status' => 'scheduled',
                        'max_attendees' => 20,
                    ],
                );

                PropertyInquiry::query()->firstOrCreate(
                    [
                        'property_id' => $featuredProperty->id,
                        'email' => 'customer@negosyohub.test',
                    ],
                    [
                        'store_id' => $store->id,
                        'name' => 'Demo Customer',
                        'phone' => '09170000002',
                        'message' => 'I would like to know the latest availability, turnover schedule, and best financing option for this listing.',
                        'status' => 'new',
                    ],
                );
            });
    }
}
