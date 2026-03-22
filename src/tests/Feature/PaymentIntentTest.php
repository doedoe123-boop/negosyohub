<?php

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\OrderPaymentMethod;
use App\OrderPaymentStatus;
use App\OrderStatus;
use App\Services\PayMongoService;
use Lunar\Models\Currency;

beforeEach(function () {
    Currency::factory()->create(['default' => true, 'code' => 'PHP']);
});

// ── POST /api/v1/orders/{order}/intent ────────────────────────────────

describe('POST /api/v1/orders/{order}/intent', function () {

    it('returns 401 for guests', function () {
        $order = Order::factory()->for(Store::factory()->create())->create([
            'status' => OrderStatus::Pending->value,
        ]);

        $this->postJson("/api/v1/orders/{$order->id}/intent")
            ->assertUnauthorized();
    });

    it('returns 403 when a different customer tries to create an intent', function () {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::factory()->for(Store::factory()->create())->create([
            'user_id' => $owner->id,
            'status' => OrderStatus::Pending->value,
        ]);

        $this->actingAs($other)
            ->postJson("/api/v1/orders/{$order->id}/intent")
            ->assertForbidden();
    });

    it('returns 422 when the order is not Pending', function () {
        $customer = User::factory()->create();
        $order = Order::factory()->for(Store::factory()->create())->create([
            'user_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value,
        ]);

        $this->actingAs($customer)
            ->postJson("/api/v1/orders/{$order->id}/intent")
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'A payment intent can only be created for a Pending order.');
    });

    it('creates a payment intent and stores it on the order', function () {
        $customer = User::factory()->create();
        $store = Store::factory()->create();
        $order = Order::factory()->for($store)->create([
            'user_id' => $customer->id,
            'status' => OrderStatus::Pending->value,
        ]);

        $this->mock(PayMongoService::class)
            ->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn([
                'id' => 'pi_test_abc123',
                'client_key' => 'pi_test_abc123_client_secret_xyz',
                'status' => 'awaiting_payment_method',
            ]);

        $this->actingAs($customer)
            ->postJson("/api/v1/orders/{$order->id}/intent")
            ->assertOk()
            ->assertJsonPath('payment_intent_id', 'pi_test_abc123')
            ->assertJsonPath('order_id', $order->id);

        $fresh = $order->fresh();
        expect($fresh->payment_intent_id)->toBe('pi_test_abc123')
            ->and($fresh->payment_status->value)->toBe(OrderPaymentStatus::Unpaid->value)
            ->and($fresh->payment_method->value)->toBe(OrderPaymentMethod::PayMongo->value);
    });

    it('is idempotent — returns existing intent without calling PayMongo again', function () {
        $customer = User::factory()->create();
        $order = Order::factory()->for(Store::factory()->create())->create([
            'user_id' => $customer->id,
            'status' => OrderStatus::Pending->value,
            'payment_intent_id' => 'pi_existing_123',
            'payment_client_key' => 'pi_existing_123_client_secret',
        ]);

        $this->mock(PayMongoService::class)
            ->shouldNotReceive('createPaymentIntent');

        $this->actingAs($customer)
            ->postJson("/api/v1/orders/{$order->id}/intent")
            ->assertOk()
            ->assertJsonPath('payment_intent_id', 'pi_existing_123');
    });

});
