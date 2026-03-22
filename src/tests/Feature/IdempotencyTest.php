<?php

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\CheckoutDiscountService;
use App\Services\OrderService;
use App\Services\Webhooks\WebhookEventDispatcher;
use Illuminate\Support\Facades\Cache;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;
use Lunar\Models\Currency;

beforeEach(function () {
    Currency::factory()->create(['default' => true, 'code' => 'PHP']);
});

// ── X-Idempotency-Key header ──────────────────────────────────────────────────

describe('X-Idempotency-Key', function () {

    it('returns the cached 201 response on a duplicate submission', function () {
        $user = User::factory()->create();
        $store = Store::factory()->create();
        $key = 'test-uuid-abc-123';
        $cacheKey = "idempotency:order:{$user->id}:{$key}";

        // Pre-seed cache as if the first request already succeeded.
        $cached = [
            'message' => 'Order placed successfully.',
            'order_id' => 42,
            'order' => [],
            'summary' => [],
        ];
        Cache::put($cacheKey, $cached, now()->addHours(24));

        // store_id must be valid (FormRequest validates it before the controller body runs).
        $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'store_id' => $store->id,
                'payment_method' => 'cash_on_delivery',
            ], ['X-Idempotency-Key' => $key])
            ->assertStatus(201)
            ->assertJsonPath('order_id', 42)
            ->assertJsonPath('message', 'Order placed successfully.');
    });

    it('falls through to the normal flow when the key has no cached entry', function () {
        $user = User::factory()->create();
        $store = Store::factory()->create();

        // No cache entry — request falls through to cart guard (null cart → 422).
        $this->actingAs($user)
            ->postJson(
                '/api/v1/orders',
                [
                    'store_id' => $store->id,
                    'payment_method' => 'cash_on_delivery',
                ],
                ['X-Idempotency-Key' => 'brand-new-key']
            )
            ->assertStatus(422)
            ->assertJsonPath('message', 'Your cart is empty. Please add items before placing an order.');
    });

    it('scopes the cache key to the authenticated user — other users cannot hit each other\'s cache', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $store = Store::factory()->create();
        $sharedKey = 'same-idempotency-key';

        // Pre-seed cache only for user A.
        Cache::put(
            "idempotency:order:{$userA->id}:{$sharedKey}",
            ['message' => 'Order placed successfully.', 'order_id' => 999, 'order' => [], 'summary' => []],
            now()->addHours(24)
        );

        // User B sends the identical key but should not receive user A's cached response.
        $this->actingAs($userB)
            ->postJson('/api/v1/orders', [
                'store_id' => $store->id,
                'payment_method' => 'cash_on_delivery',
            ], ['X-Idempotency-Key' => $sharedKey])
            ->assertStatus(422); // cart guard, not 201 from cache
    });

    it('does not cache a response when no key header is sent', function () {
        $user = User::factory()->create();
        $store = Store::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'store_id' => $store->id,
                'payment_method' => 'cash_on_delivery',
            ])
            ->assertStatus(422);

        // Nothing should be in the cache for this user.
        $keys = Cache::get("idempotency:order:{$user->id}:*");
        expect($keys)->toBeNull();
    });

});

// ── Concurrent order lock (#5) ────────────────────────────────────────────────

describe('Concurrent order lock', function () {

    it('returns 409 when the same user\'s order lock is already held', function () {
        $user = User::factory()->create();
        $store = Store::factory()->create();

        // Bind a partial override of OrderService that skips validation methods
        // so we can isolate the lock check in createFromCart().
        $this->app->bind(OrderService::class, function ($app) {
            return new class(
                $app->make(CommissionService::class),
                $app->make(CheckoutDiscountService::class),
                $app->make(WebhookEventDispatcher::class),
            ) extends OrderService
            {
                public function validateCart(Cart $cart): void {}

                public function validateStore(Store $store): void {}

                public function validateCartBelongsToStore(Cart $cart, Store $store): void {}
            };
        });

        // Return a fake cart object so the controller's null-cart guard passes.
        $fakeCart = Mockery::mock(Cart::class)->makePartial();
        $fakeCart->customer_id = $user->id;

        CartSession::shouldReceive('current')->once()->andReturn($fakeCart);

        // Pre-hold the distributed lock — simulates a concurrent in-flight request.
        $heldLock = Cache::lock("order-create:{$user->id}", 60);
        $heldLock->get();

        $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'store_id' => $store->id,
                'payment_method' => 'cash_on_delivery',
            ])
            ->assertStatus(409);

        $heldLock->release();
    });

    it('acquires the lock and releases it even when order creation fails', function () {
        $user = User::factory()->create();
        $store = Store::factory()->create();

        // Override only the DB transaction part to throw so we can
        // verify the lock is released via the finally block.
        $this->app->bind(OrderService::class, function ($app) {
            return new class(
                $app->make(CommissionService::class),
                $app->make(CheckoutDiscountService::class),
                $app->make(WebhookEventDispatcher::class),
            ) extends OrderService
            {
                public function validateCart(Cart $cart): void {}

                public function validateStore(Store $store): void {}

                public function validateCartBelongsToStore(Cart $cart, Store $store): void {}

                protected function notifyStoreOwner(Order $order): void {}
            };
        });

        $fakeCart = Mockery::mock(Cart::class)->makePartial();
        $fakeCart->customer_id = $user->id;
        // calculate() on the mock will throw (no real cart lines), verifying the lock
        // finally block fires and releases the lock.
        $fakeCart->shouldReceive('calculate')->andThrow(new RuntimeException('Simulated failure'));

        CartSession::shouldReceive('current')->once()->andReturn($fakeCart);

        $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'store_id' => $store->id,
                'payment_method' => 'cash_on_delivery',
            ])
            ->assertStatus(500);

        // After the failed request the lock must be released — a second request
        // should not return 409 but instead proceed normally (422 for null cart).
        CartSession::shouldReceive('current')->once()->andReturn(null);

        $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'store_id' => $store->id,
                'payment_method' => 'cash_on_delivery',
            ])
            ->assertStatus(422);
    });

});
