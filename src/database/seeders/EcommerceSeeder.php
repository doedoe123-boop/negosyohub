<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Lunar\Models\Product;

class EcommerceSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            LunarCatalogSeeder::class,
            ProductSeeder::class,
            ShippingSeeder::class,
        ]);

        Store::query()
            ->where('sector', 'ecommerce')
            ->get()
            ->each(function (Store $store): void {
                Review::query()->firstOrCreate(
                    [
                        'store_id' => $store->id,
                        'reviewable_type' => Store::class,
                        'reviewable_id' => $store->id,
                        'reviewer_email' => 'customer@negosyohub.test',
                    ],
                    Review::factory()->published()->verifiedPurchase()->make([
                        'store_id' => $store->id,
                        'reviewable_type' => Store::class,
                        'reviewable_id' => $store->id,
                        'reviewer_name' => 'Demo Customer',
                        'reviewer_email' => 'customer@negosyohub.test',
                    ])->toArray(),
                );

                $products = Product::query()
                    ->whereJsonContains('attribute_data->store_id->value', $store->id)
                    ->limit(2)
                    ->get();

                foreach ($products as $product) {
                    Review::query()->firstOrCreate(
                        [
                            'store_id' => $store->id,
                            'reviewable_type' => Product::class,
                            'reviewable_id' => $product->id,
                            'reviewer_email' => 'customer@negosyohub.test',
                        ],
                        Review::factory()->published()->verifiedPurchase()->make([
                            'store_id' => $store->id,
                            'reviewable_type' => Product::class,
                            'reviewable_id' => $product->id,
                            'reviewer_name' => 'Demo Customer',
                            'reviewer_email' => 'customer@negosyohub.test',
                            'title' => 'Reliable demo product',
                            'content' => 'This seeded demo product renders correctly with matching copy, pricing, and images in the storefront.',
                        ])->toArray(),
                    );
                }
            });
    }
}
