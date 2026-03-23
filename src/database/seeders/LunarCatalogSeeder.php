<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\Toggle;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Attribute;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Brand;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;
use Lunar\Models\ProductOption;
use Lunar\Models\ProductOptionValue;
use Lunar\Models\ProductType;
use Lunar\Models\Tag;

/**
 * Seeds global Lunar catalog configuration for the marketplace:
 * product types, attribute groups, collections, brands, product options,
 * and tags that match the seeded storefront catalog.
 *
 * These are platform-wide — not scoped to a specific store.
 */
class LunarCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedProductTypes();
        $this->seedCollectionGroups();
        $this->seedBrands();
        $this->seedProductOptions();
        $this->seedTags();
    }

    /**
     * Seed product types with attribute groups and attributes.
     */
    private function seedProductTypes(): void
    {
        // === Attribute Group: Product Details ===
        $detailsGroup = AttributeGroup::firstOrCreate(
            ['handle' => 'product-details'],
            [
                'attributable_type' => 'product',
                'name' => collect(['en' => 'Product Details']),
                'position' => 1,
            ]
        );

        $detailAttributes = [
            [
                'handle' => 'name',
                'name' => collect(['en' => 'Name']),
                'type' => TranslatedText::class,
                'required' => true,
                'system' => true,
                'position' => 1,
                'section' => 'main',
                'searchable' => true,
            ],
            [
                'handle' => 'description',
                'name' => collect(['en' => 'Description']),
                'type' => TranslatedText::class,
                'required' => false,
                'system' => false,
                'position' => 2,
                'section' => 'main',
                'searchable' => true,
            ],
        ];

        foreach ($detailAttributes as $attr) {
            Attribute::firstOrCreate(
                ['handle' => $attr['handle'], 'attribute_type' => 'product'],
                array_merge($attr, [
                    'attribute_group_id' => $detailsGroup->id,
                    'configuration' => collect(),
                    'filterable' => false,
                ])
            );
        }

        // === Attribute Group: Pricing & Availability ===
        $pricingGroup = AttributeGroup::firstOrCreate(
            ['handle' => 'pricing-availability'],
            [
                'attributable_type' => 'product',
                'name' => collect(['en' => 'Pricing & Availability']),
                'position' => 2,
            ]
        );

        $pricingAttributes = [
            [
                'handle' => 'preparation_time',
                'name' => collect(['en' => 'Preparation Time (mins)']),
                'type' => Text::class,
                'required' => false,
                'system' => false,
                'position' => 1,
                'section' => 'main',
                'searchable' => false,
                'filterable' => true,
            ],
            [
                'handle' => 'is_available',
                'name' => collect(['en' => 'Available']),
                'type' => Toggle::class,
                'required' => false,
                'system' => false,
                'position' => 2,
                'section' => 'main',
                'searchable' => false,
                'filterable' => true,
            ],
        ];

        foreach ($pricingAttributes as $attr) {
            Attribute::firstOrCreate(
                ['handle' => $attr['handle'], 'attribute_type' => 'product'],
                array_merge($attr, [
                    'attribute_group_id' => $pricingGroup->id,
                    'configuration' => collect(),
                ])
            );
        }

        // === Product Types ===
        $productTypes = [
            'Electronics' => [$detailsGroup, $pricingGroup],
            'Clothing' => [$detailsGroup, $pricingGroup],
            'Home & Living' => [$detailsGroup, $pricingGroup],
            'Food & Grocery' => [$detailsGroup, $pricingGroup],
            'Health & Beauty' => [$detailsGroup, $pricingGroup],
            'Sports & Fitness' => [$detailsGroup, $pricingGroup],
            'Books & Stationery' => [$detailsGroup, $pricingGroup],
            'Toys & Hobbies' => [$detailsGroup, $pricingGroup],
            'Automotive' => [$detailsGroup, $pricingGroup],
            'Pets & Accessories' => [$detailsGroup, $pricingGroup],
            'Beverage' => [$detailsGroup, $pricingGroup],
        ];

        foreach ($productTypes as $name => $groups) {
            $type = ProductType::firstOrCreate(['name' => $name]);

            // Map attribute groups → attributes to the product type
            foreach ($groups as $group) {
                $attributes = Attribute::where('attribute_group_id', $group->id)->get();
                $type->mappedAttributes()->syncWithoutDetaching($attributes->pluck('id'));
            }
        }
    }

    /**
     * Seed collection groups and default collections.
     */
    private function seedCollectionGroups(): void
    {
        $groups = [
            'marketplace-categories' => [
                'name' => 'Marketplace Categories',
                'collections' => [
                    'Electronics',
                    'Fashion',
                    'Food',
                    'Home & Living',
                    'Beauty',
                    'Sports',
                    'Books',
                    'Pets',
                ],
            ],
            'shopping-highlights' => [
                'name' => 'Shopping Highlights',
                'collections' => [
                    'Best Sellers',
                    'New Arrivals',
                    'Everyday Essentials',
                    'Gift Picks',
                ],
            ],
            'storefront-curation' => [
                'name' => 'Storefront Curation',
                'collections' => [
                    'Local Favorites',
                    'Eco-Friendly',
                    'Premium Picks',
                ],
            ],
        ];

        foreach ($groups as $handle => $data) {
            $group = CollectionGroup::firstOrCreate(
                ['handle' => $handle],
                ['name' => $data['name']]
            );

            foreach ($data['collections'] as $collectionName) {
                $exists = Collection::query()
                    ->where('collection_group_id', $group->id)
                    ->whereJsonContains('attribute_data->name->value->en', $collectionName)
                    ->exists();

                if (! $exists) {
                    Collection::create([
                        'collection_group_id' => $group->id,
                        'sort' => 'custom',
                        'attribute_data' => collect([
                            'name' => new TranslatedText(collect(['en' => $collectionName])),
                        ]),
                    ]);
                }
            }
        }
    }

    /**
     * Seed marketplace-friendly demo brands.
     */
    private function seedBrands(): void
    {
        $brands = [
            'TechNest Essentials',
            'FreshBasket Select',
            'StyleForward Studio',
            'GreenHome Living',
            'SportzHub Performance',
            'CosmetiQ Care',
            'ToyWorld Kids',
            'AutoGear Road',
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(['name' => $brand]);
        }
    }

    /**
     * Seed shared product options used by marketplace demo products.
     */
    private function seedProductOptions(): void
    {
        $options = [
            'size' => [
                'label' => 'Size',
                'values' => ['Small', 'Medium', 'Large', 'Extra Large'],
            ],
            'color' => [
                'label' => 'Color',
                'values' => ['Black', 'White', 'Navy', 'Beige', 'Olive'],
            ],
            'storage' => [
                'label' => 'Storage',
                'values' => ['128GB', '256GB', '512GB', '1TB'],
            ],
            'pack-size' => [
                'label' => 'Pack Size',
                'values' => ['Single', '2-Pack', '4-Pack', 'Family Pack'],
            ],
        ];

        foreach ($options as $handle => $data) {
            $option = ProductOption::firstOrCreate(
                ['handle' => $handle],
                [
                    'name' => ['en' => $data['label']],
                    'label' => ['en' => $data['label']],
                    'shared' => true,
                ]
            );

            foreach ($data['values'] as $position => $valueName) {
                $exists = ProductOptionValue::where('product_option_id', $option->id)
                    ->whereJsonContains('name->en', $valueName)
                    ->exists();

                if (! $exists) {
                    ProductOptionValue::create([
                        'product_option_id' => $option->id,
                        'name' => ['en' => $valueName],
                        'position' => $position + 1,
                    ]);
                }
            }
        }
    }

    /**
     * Seed common tags for demo merchandising and storefront chips.
     */
    private function seedTags(): void
    {
        $tags = [
            'bestseller',
            'new-arrival',
            'giftable',
            'eco-friendly',
            'essentials',
            'premium',
            'budget-pick',
            'promo',
            'limited-stock',
            'top-rated',
            'local-made',
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(['value' => $tag]);
        }
    }
}
