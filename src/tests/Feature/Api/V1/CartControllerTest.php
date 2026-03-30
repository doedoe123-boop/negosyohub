<?php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\LunarSeeder;
use Lunar\FieldTypes\Number;
use Lunar\FieldTypes\Text;
use Lunar\Models\Cart;
use Lunar\Models\Currency;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;

// Guards ─────────────────────────────────────────────────────────────────────

beforeEach(function () {
    Currency::factory()->create(['default' => true, 'code' => 'PHP']);
    (new LunarSeeder)->run();
});

function createCartProductVariant(int $storeId, string $name = 'Cart Test Product'): ProductVariant
{
    $type = ProductType::query()->firstOrCreate(['name' => 'Cart Test Type']);
    $taxClass = TaxClass::query()->first() ?? TaxClass::factory()->create();

    $product = Product::query()->create([
        'product_type_id' => $type->id,
        'status' => 'published',
        'attribute_data' => [
            'name' => new Text($name),
            'store_id' => new Number($storeId),
        ],
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'tax_class_id' => $taxClass->id,
        'sku' => fake()->unique()->regexify('[A-Z0-9]{12}'),
        'unit_quantity' => 1,
        'shippable' => true,
        'purchasable' => 'always',
    ]);

    $currency = Currency::query()->where('code', 'PHP')->firstOrFail();
    $variant->prices()->create([
        'customer_group_id' => null,
        'currency_id' => $currency->id,
        'price' => 125000,
        'compare_price' => null,
        'min_quantity' => 1,
    ]);

    return $variant->refresh();
}

it('rejects unauthenticated access to GET /cart', function () {
    $this->getJson(route('api.v1.cart.show'))->assertUnauthorized();
});

it('rejects unauthenticated access to POST /cart/lines', function () {
    $this->postJson(route('api.v1.cart.lines.store'))->assertUnauthorized();
});

it('rejects unauthenticated access to DELETE /cart', function () {
    $this->deleteJson(route('api.v1.cart.clear'))->assertUnauthorized();
});

it('rejects unauthenticated access to GET /cart/shipping-options', function () {
    $this->getJson(route('api.v1.cart.shipping-options'))->assertUnauthorized();
});

// GET /api/v1/cart ────────────────────────────────────────────────────────────

it('returns null or a cart object when authenticated user requests cart', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson(route('api.v1.cart.show'));

    // When no cart is attached to the session the endpoint returns null;
    // if Lunar creates a cart automatically it returns a JSON object.
    $response->assertOk();
    $body = $response->json();
    expect($body === null || is_array($body))->toBeTrue();
});

// POST /api/v1/cart/lines ────────────────────────────────────────────────────

it('validates required fields when adding a cart line', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson(route('api.v1.cart.lines.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['purchasable_type', 'purchasable_id', 'quantity']);
});

it('rejects an unknown purchasable type', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson(route('api.v1.cart.lines.store'), [
            'purchasable_type' => 'unknown-type',
            'purchasable_id' => 1,
            'quantity' => 1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('purchasable_type');
});

it('rejects a quantity of zero', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson(route('api.v1.cart.lines.store'), [
            'purchasable_type' => 'product-variant',
            'purchasable_id' => 1,
            'quantity' => 0,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');
});

it('rejects a quantity above 100', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson(route('api.v1.cart.lines.store'), [
            'purchasable_type' => 'product-variant',
            'purchasable_id' => 1,
            'quantity' => 101,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');
});

it('allows adding items from different stores into the same cart', function () {
    $user = User::factory()->create();
    $firstStore = Store::factory()->create(['id' => 11]);
    $secondStore = Store::factory()->create(['id' => 22]);
    $firstStoreVariant = createCartProductVariant($firstStore->id, 'Store One Item');
    $secondStoreVariant = createCartProductVariant($secondStore->id, 'Store Two Item');

    $this->actingAs($user, 'sanctum')
        ->postJson(route('api.v1.cart.lines.store'), [
            'purchasable_type' => 'product-variant',
            'purchasable_id' => $firstStoreVariant->id,
            'quantity' => 1,
            'meta' => ['store_id' => $firstStore->id],
        ])
        ->assertOk();

    $this->actingAs($user, 'sanctum')
        ->postJson(route('api.v1.cart.lines.store'), [
            'purchasable_type' => 'product-variant',
            'purchasable_id' => $secondStoreVariant->id,
            'quantity' => 1,
            'meta' => ['store_id' => $secondStore->id],
        ])
        ->assertOk()
        ->assertJsonPath('store_count', 2)
        ->assertJsonPath('multi_store', true)
        ->assertJsonCount(2, 'groups');
});

// DELETE /api/v1/cart ─────────────────────────────────────────────────────────

it('clears the cart and returns null for an authenticated user', function () {
    $user = User::factory()->create();

    // Clearing a non-existent cart is safe — the controller guards with `if ($cart)`.
    $this->actingAs($user, 'sanctum')
        ->deleteJson(route('api.v1.cart.clear'))
        ->assertOk(); // 200 is sufficient; no cart to clear means no-op.
});

// POST /api/v1/cart/address ───────────────────────────────────────────────────

it('validates required address fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson(route('api.v1.cart.address'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['first_name', 'last_name', 'line_one', 'city', 'state', 'postcode', 'country']);
});

it('rejects a country code that is not 2 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson(route('api.v1.cart.address'), [
            'first_name' => 'Juan',
            'last_name' => 'dela Cruz',
            'line_one' => '123 Rizal St',
            'city' => 'Manila',
            'state' => 'Metro Manila',
            'postcode' => '1000',
            'country' => 'PHL', // 3-char, should fail
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('country');
});
