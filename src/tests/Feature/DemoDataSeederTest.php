<?php

use App\Models\Development;
use App\Models\MovingAddOn;
use App\Models\MovingBooking;
use App\Models\Order;
use App\Models\Property;
use App\Models\RentalAgreement;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use App\OrderPaymentMethod;
use App\OrderPaymentStatus;
use Database\Seeders\DatabaseSeeder;
use Lunar\Models\Product;

it('seeds demo accounts and connected sector data that matches storefront flows', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'admin@negosyohub.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'customer@negosyohub.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'tenant@negosyohub.test')->exists())->toBeTrue();

    $ecommerceStore = Store::query()->where('slug', 'technest')->firstOrFail();
    $realtyStore = Store::query()->where('slug', 'premier-realty-makati')->firstOrFail();
    $rentalStore = Store::query()->where('slug', 'urbannest-rentals')->firstOrFail();
    $movingStore = Store::query()->where('slug', 'bayanihan-movers')->firstOrFail();

    expect(Product::query()->whereJsonContains('attribute_data->store_id->value', $ecommerceStore->id)->count())->toBeGreaterThan(0)
        ->and(Development::query()->where('store_id', $realtyStore->id)->count())->toBeGreaterThan(0)
        ->and(Property::query()->where('store_id', $rentalStore->id)->where('listing_type', 'for_rent')->count())->toBeGreaterThan(0)
        ->and(RentalAgreement::query()->where('store_id', $rentalStore->id)->count())->toBeGreaterThan(0)
        ->and(MovingAddOn::query()->where('store_id', $movingStore->id)->count())->toBeGreaterThan(0)
        ->and(MovingBooking::query()->where('store_id', $movingStore->id)->count())->toBeGreaterThan(0);

    $demoOrder = Order::query()->where('reference', 'DEMO-COD-TECHNEST')->firstOrFail();

    expect($demoOrder->payment_method)->toBe(OrderPaymentMethod::CashOnDelivery)
        ->and($demoOrder->payment_status)->toBe(OrderPaymentStatus::Unpaid)
        ->and($demoOrder->lines()->count())->toBeGreaterThan(1)
        ->and($demoOrder->addresses()->count())->toBeGreaterThan(0)
        ->and(Shipment::query()->where('order_id', $demoOrder->id)->count())->toBeGreaterThan(0);
});
