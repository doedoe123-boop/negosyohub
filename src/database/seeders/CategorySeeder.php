<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;

class CategorySeeder extends Seeder
{
    /**
     * Seed the "Marketplace Categories" CollectionGroup used by the category
     * strip on the storefront.
     *
     * Run with: php artisan db:seed --class=CategorySeeder
     */
    public function run(): void
    {
        $group = CollectionGroup::firstOrCreate(
            ['handle' => 'marketplace-categories'],
            ['name' => 'Marketplace Categories'],
        );

        $categories = [
            'Electronics',
            'Fashion',
            'Food',
            'Home & Living',
            'Beauty',
            'Sports',
            'Gadgets',
            'Books',
        ];

        foreach ($categories as $name) {
            $exists = Collection::query()
                ->where('collection_group_id', $group->id)
                ->whereJsonContains('attribute_data->name->value->en', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            Collection::create([
                'collection_group_id' => $group->id,
                'sort' => 'custom',
                'attribute_data' => collect([
                    'name' => new TranslatedText(collect(['en' => $name])),
                ]),
            ]);
        }

        $this->command->info('Marketplace categories seeded.');
    }
}
