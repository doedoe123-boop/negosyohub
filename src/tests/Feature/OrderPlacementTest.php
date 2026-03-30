<?php

use App\Models\Store;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Support\Facades\RateLimiter;

/**
 * @see /skills/order-processing.md
 * @see /skills/commission-calculation.md
 */
describe('Order Placement', function () {

    it('rejects unauthenticated users', function () {
        $this->postJson('/api/v1/orders')
            ->assertUnauthorized();
    });

    it('rejects non-customer roles', function () {
        $user = User::factory()->storeOwner()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/orders', ['store_id' => 1])
            ->assertForbidden();
    });

    it('requires a payment method', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/orders', [])
            ->assertJsonValidationErrors(['payment_method']);
    });

    it('rejects a non-existent store', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/orders', ['store_id' => 99999])
            ->assertJsonValidationErrors(['store_id']);
    });

    it('rejects orders to a pending store', function () {
        $user = User::factory()->create();
        $store = Store::factory()->pending()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'store_id' => $store->id,
                'payment_method' => 'cash_on_delivery',
            ])
            ->assertJsonValidationErrors(['store_id']);
    });

    it('rejects orders to a suspended store', function () {
        $user = User::factory()->create();
        $store = Store::factory()->suspended()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'store_id' => $store->id,
                'payment_method' => 'cash_on_delivery',
            ])
            ->assertJsonValidationErrors(['store_id']);
    });
});

describe('Commission Calculation', function () {

    it('calculates commission correctly for a given total and rate', function () {
        $store = Store::factory()->create(['commission_rate' => 15.00]);
        $service = app(CommissionService::class);

        $result = $service->calculate(10000, $store);

        expect($result)->toMatchArray([
            'commission_amount' => 1500,
            'store_earning' => 8500,
            'platform_earning' => 1500,
        ]);
    });

    it('handles zero commission rate', function () {
        $store = Store::factory()->create(['commission_rate' => 0.00]);
        $service = app(CommissionService::class);

        $result = $service->calculate(10000, $store);

        expect($result)->toMatchArray([
            'commission_amount' => 0,
            'store_earning' => 10000,
            'platform_earning' => 0,
        ]);
    });

    it('handles 100 percent commission rate', function () {
        $store = Store::factory()->create(['commission_rate' => 100.00]);
        $service = app(CommissionService::class);

        $result = $service->calculate(10000, $store);

        expect($result)->toMatchArray([
            'commission_amount' => 10000,
            'store_earning' => 0,
            'platform_earning' => 10000,
        ]);
    });

    it('rounds fractional commissions to the nearest integer', function () {
        $store = Store::factory()->create(['commission_rate' => 7.50]);
        $service = app(CommissionService::class);

        $result = $service->calculate(333, $store);

        // 333 * 0.075 = 24.975 → rounds to 25
        expect($result['commission_amount'])->toBe(25);
        expect($result['store_earning'])->toBe(308);
    });
});

describe('Checkout rate limiting', function () {

    afterEach(function () {
        RateLimiter::clear('user:1:checkout-orders');
        RateLimiter::clear('user:2:checkout-orders');
    });

    it('rate limits checkout attempts per authenticated user instead of by shared IP', function () {
        $firstBuyer = User::factory()->create(['id' => 1]);
        $secondBuyer = User::factory()->create(['id' => 2]);
        $store = Store::factory()->create();

        for ($attempt = 0; $attempt < 12; $attempt++) {
            $this->actingAs($firstBuyer)
                ->postJson('/api/v1/orders', [
                    'store_id' => $store->id,
                    'payment_method' => 'cash_on_delivery',
                ])
                ->assertStatus(422);
        }

        $this->actingAs($firstBuyer)
            ->postJson('/api/v1/orders', [
                'store_id' => $store->id,
                'payment_method' => 'cash_on_delivery',
            ])
            ->assertStatus(429);

        $this->actingAs($secondBuyer)
            ->postJson('/api/v1/orders', [
                'store_id' => $store->id,
                'payment_method' => 'cash_on_delivery',
            ])
            ->assertStatus(422);
    });
});
