<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;
use Lunar\FieldTypes\Number;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Attribute;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;

/**
 * Seeds Lunar e-commerce products for all 10 e-commerce stores.
 * Catalog data lives in data/products.php (keyed by store slug).
 *
 * Depends on: LunarSeeder, LunarCatalogSeeder, StoreSeeder.
 */
class ProductSeeder extends Seeder
{
    private Channel $channel;

    private Currency $currency;

    private TaxClass $taxClass;

    private CustomerGroup $customerGroup;

    public function run(): void
    {
        $this->channel = Channel::whereDefault(true)->firstOrFail();
        $this->currency = Currency::whereDefault(true)->firstOrFail();
        $this->taxClass = TaxClass::first() ?? throw new \RuntimeException('Run LunarSeeder first.');
        $this->customerGroup = CustomerGroup::whereDefault(true)->firstOrFail();

        $this->ensureProductTypes();

        $catalogs = require __DIR__.'/data/products.php';

        foreach ($catalogs as $slug => $items) {
            $store = Store::where('slug', $slug)->first();

            if (! $store) {
                $this->command->getOutput()->writeln("<comment>ProductSeeder: store [{$slug}] not found — skipping.</comment>");

                continue;
            }

            if (Product::whereJsonContains('attribute_data->store_id->value', $store->id)->exists()) {
                $this->command->getOutput()->writeln("<comment>ProductSeeder: [{$slug}] already seeded — skipping.</comment>");

                continue;
            }

            foreach ($items as $item) {
                $this->createProduct($item, $store->id);
            }
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Create one Lunar product with a default variant and a base price.
     *
     * @param array{
     *     name: string,
     *     description: string,
     *     type: string,
     *     sku: string,
     *     price: int,
     *     compare_price: int|null,
     *     stock: int,
     * } $data
     */
    private function createProduct(array $data, int $storeId): void
    {
        $productType = ProductType::where('name', $data['type'])->firstOrFail();

        $product = Product::create([
            'product_type_id' => $productType->id,
            'status' => 'published',
            'attribute_data' => collect([
                'name' => new TranslatedText(collect(['en' => $data['name']])),
                'description' => new TranslatedText(collect(['en' => $data['description']])),
                // Wrapped in Number so AsAttributeData cast can serialise it.
                // Queried via whereJsonContains('attribute_data->store_id->value', $storeId).
                'store_id' => new Number($storeId),
            ]),
        ]);

        // Enable the product on the default channel.
        $product->channels()->sync([
            $this->channel->id => [
                'enabled' => true,
                'starts_at' => now(),
                'ends_at' => null,
            ],
        ]);

        // Make it visible and purchasable for the default customer group.
        $product->customerGroups()->sync([
            $this->customerGroup->id => [
                'enabled' => true,
                'visible' => true,
                'purchasable' => true,
                'starts_at' => null,
                'ends_at' => null,
            ],
        ]);

        // One default variant (no size/colour options — simple product).
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $this->taxClass->id,
            'sku' => $data['sku'],
            'stock' => $data['stock'],
            'purchasable' => 'always',
            'shippable' => true,
            'unit_quantity' => 1,
            'backorder' => 0,
        ]);

        // Base price — null customer_group_id means it applies to all groups.
        $variant->prices()->create([
            'customer_group_id' => null,
            'currency_id' => $this->currency->id,
            'price' => $data['price'],
            'compare_price' => $data['compare_price'],
            'min_quantity' => 1,
        ]);
    }

    /**
     * Ensure the product types used by this seeder exist, and map the standard
     * Lunar attribute groups to them (created by LunarCatalogSeeder).
     */
    private function ensureProductTypes(): void
    {
        $detailsGroup = AttributeGroup::where('handle', 'product-details')
            ->where('attributable_type', 'product')
            ->first();

        $pricingGroup = AttributeGroup::where('handle', 'pricing-availability')
            ->where('attributable_type', 'product')
            ->first();

        $types = [
            'Electronics',
            'Clothing',
            'Home & Living',
            'Food & Grocery',
            'Health & Beauty',
            'Sports & Fitness',
            'Books & Stationery',
            'Toys & Hobbies',
            'Automotive',
            'Pets & Accessories',
            'Beverage',
        ];

        foreach ($types as $typeName) {
            $type = ProductType::firstOrCreate(['name' => $typeName]);

            foreach (array_filter([$detailsGroup, $pricingGroup]) as $group) {
                $attrs = Attribute::where('attribute_group_id', $group->id)
                    ->where('attribute_type', 'product')
                    ->get();

                $type->mappedAttributes()->syncWithoutDetaching($attrs->pluck('id'));
            }
        }
    }
}
