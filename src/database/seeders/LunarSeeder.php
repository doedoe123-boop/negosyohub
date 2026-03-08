<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Lunar\Models\Channel;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Language;
use Lunar\Models\TaxClass;
use Lunar\Models\TaxZone;

class LunarSeeder extends Seeder
{
    /**
     * Seed the essential Lunar data (channels, language, currency, etc.).
     */
    public function run(): void
    {
        if (! Channel::whereDefault(true)->exists()) {
            Channel::create([
                'name' => 'Webstore',
                'handle' => 'webstore',
                'default' => true,
                'url' => 'http://localhost',
            ]);
        }

        if (! Language::count()) {
            Language::create([
                'code' => 'en',
                'name' => 'English',
                'default' => true,
            ]);
        }

        if (! Currency::whereDefault(true)->exists()) {
            Currency::create([
                'code' => 'PHP',
                'name' => 'Philippine Peso',
                'exchange_rate' => 1,
                'decimal_places' => 2,
                'default' => true,
                'enabled' => true,
            ]);
        }

        if (! CustomerGroup::whereDefault(true)->exists()) {
            CustomerGroup::create([
                'name' => 'Retail',
                'handle' => 'retail',
                'default' => true,
            ]);
        }

        if (! CollectionGroup::count()) {
            CollectionGroup::create([
                'name' => 'Main',
                'handle' => 'main',
            ]);
        }

        if (! TaxClass::count()) {
            TaxClass::create([
                'name' => 'Default Tax Class',
                'default' => true,
            ]);
        }

        if (! TaxZone::count()) {
            TaxZone::create([
                'name' => 'Default Tax Zone',
                'zone_type' => 'country',
                'price_display' => 'tax_exclusive',
                'default' => true,
                'active' => true,
            ]);
        }

        if (! Country::count() && ! app()->environment('testing')) {
            Artisan::call('lunar:import:address-data');
        }
    }
}
