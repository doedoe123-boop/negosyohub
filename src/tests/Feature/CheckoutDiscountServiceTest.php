<?php

use App\CouponScope;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Store;
use App\Services\CheckoutDiscountService;
use Illuminate\Validation\ValidationException;
use Lunar\DataTypes\Price;
use Lunar\Models\Cart;
use Lunar\Models\Currency;

beforeEach(function () {
    Currency::factory()->create([
        'default' => true,
        'code' => 'PHP',
    ]);
});

it('rejects sector coupons for the wrong store sector', function () {
    $service = app(CheckoutDiscountService::class);
    $store = Store::factory()->create(['sector' => 'ecommerce']);
    $coupon = Coupon::factory()
        ->active()
        ->percentage(15)
        ->forSector('real_estate')
        ->create();

    $currency = Currency::query()->where('code', 'PHP')->firstOrFail();
    $cart = Mockery::mock(Cart::class)->makePartial();
    $cart->meta = [];
    $cart->subTotal = new Price(20000, $currency);
    $cart->shippingTotal = new Price(1000, $currency);
    $cart->total = new Price(21000, $currency);
    $cart->shouldReceive('calculate')->andReturnSelf();

    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('This coupon is not valid for this sector.');

    $service->validateAndCalculate($coupon->code, $cart, $store);
});

it('summarizes an applied coupon using the recalculated cart totals', function () {
    $service = app(CheckoutDiscountService::class);
    $store = Store::factory()->create(['sector' => 'ecommerce']);
    $coupon = Coupon::factory()
        ->active()
        ->percentage(10)
        ->create([
            'scope' => CouponScope::Global,
            'max_discount_cents' => 1500,
        ]);

    $currency = Currency::query()->where('code', 'PHP')->firstOrFail();
    $cart = Mockery::mock(Cart::class)->makePartial();
    $cart->meta = [
        'applied_coupon' => [
            'coupon_id' => $coupon->id,
        ],
    ];
    $cart->subTotal = new Price(20000, $currency);
    $cart->shippingTotal = new Price(2500, $currency);
    $cart->total = new Price(22500, $currency);
    $cart->shouldReceive('calculate')->andReturnSelf();

    $summary = $service->summarizeCart($cart, $store);

    expect($summary['discount_amount'])->toBe(1500)
        ->and($summary['total_after_discount'])->toBe(21000)
        ->and($summary['applied_coupon'])->toMatchArray([
            'coupon_id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type->value,
            'discount_amount' => 1500,
            'discount_formatted' => '₱15.00',
        ]);
});

it('applies the coupon to the order totals and increments usage', function () {
    $service = app(CheckoutDiscountService::class);
    $store = Store::factory()->create(['sector' => 'ecommerce']);
    $coupon = Coupon::factory()
        ->active()
        ->fixedAmount(2500)
        ->create([
            'scope' => CouponScope::Global,
            'times_used' => 0,
        ]);

    $order = Order::factory()->create([
        'store_id' => $store->id,
        'total' => 25000,
        'discount_total' => 0,
        'meta' => [],
    ]);

    $currency = Currency::query()->where('code', 'PHP')->firstOrFail();
    $cart = Mockery::mock(Cart::class)->makePartial();
    $cart->meta = [
        'applied_coupon' => [
            'coupon_id' => $coupon->id,
        ],
    ];
    $cart->subTotal = new Price(25000, $currency);
    $cart->shippingTotal = new Price(0, $currency);
    $cart->total = new Price(25000, $currency);
    $cart->shouldReceive('calculate')->andReturnSelf();

    $updatedOrder = $service->applyToOrder($order, $cart, $store);

    expect($updatedOrder->discount_total->value)->toBe(2500)
        ->and($updatedOrder->total->value)->toBe(22500)
        ->and($updatedOrder->meta['applied_coupon'])->toMatchArray([
            'coupon_id' => $coupon->id,
            'code' => $coupon->code,
            'discount_amount' => 2500,
        ]);

    expect($coupon->fresh()->times_used)->toBe(1);
});
