<?php

use App\Models\Order;
use App\Models\Store;
use App\OrderPaymentStatus;
use App\OrderStatus;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Currency;

beforeEach(function () {
    Currency::factory()->create(['default' => true, 'code' => 'PHP']);
    // Seed a test webhook secret so signature verification works in tests.
    config(['services.paymongo.webhook_secret_test' => 'test_webhook_secret']);
});

/**
 * Build a valid Paymongo-Signature header for the given payload.
 * Format: t={timestamp},te={hmac},li={hmac}
 */
function makePayMongoSignature(string $rawPayload, string $secret, int $timestamp = 0): string
{
    $ts = $timestamp ?: time();
    $signed = "{$ts}.{$rawPayload}";
    $hmac = hash_hmac('sha256', $signed, $secret);

    return "t={$ts},te={$hmac},li={$hmac}";
}

/**
 * Build the PayMongo payment event JSON payload.
 */
function makePaymentEventPayload(string $type, string $paymentIntentId): string
{
    return json_encode([
        'data' => [
            'attributes' => [
                'type' => $type,
                'data' => [
                    'id' => $paymentIntentId,
                    'attributes' => [
                        'payment_intent_id' => $paymentIntentId,
                        'status' => $type === 'payment.paid' ? 'paid' : 'failed',
                    ],
                ],
            ],
        ],
    ]);
}

// ── POST /webhooks/paymongo ───────────────────────────────────────────

describe('POST /webhooks/paymongo', function () {

    it('returns 401 for a missing signature', function () {
        $this->postJson('/api/webhooks/paymongo', ['data' => []])
            ->assertUnauthorized();
    });

    it('returns 401 for a tampered signature', function () {
        $payload = makePaymentEventPayload('payment.paid', 'pi_test_123');

        $this->call(
            'POST',
            '/api/webhooks/paymongo',
            [],
            [],
            [],
            ['HTTP_PAYMONGO-SIGNATURE' => 't='.time().',te=badhash,li=badhash',
                'CONTENT_TYPE' => 'application/json'],
            $payload
        )->assertUnauthorized();
    });

    it('marks a Pending order as paid on payment.paid', function () {
        $order = Order::factory()->for(Store::factory()->create())->create([
            'status' => OrderStatus::Pending->value,
            'payment_intent_id' => 'pi_paid_test',
        ]);

        $payload = makePaymentEventPayload('payment.paid', 'pi_paid_test');
        $signature = makePayMongoSignature($payload, 'test_webhook_secret');

        $this->call(
            'POST',
            '/api/webhooks/paymongo',
            [],
            [],
            [],
            ['HTTP_PAYMONGO-SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        )->assertOk();

        $fresh = $order->fresh();
        expect($fresh->status)->toBe(OrderStatus::Pending->value)
            ->and($fresh->payment_status->value)->toBe(OrderPaymentStatus::Paid->value)
            ->and($fresh->paid_at)->not->toBeNull();
    });

    it('cancels a Pending order on payment.failed', function () {
        $order = Order::factory()->for(Store::factory()->create())->create([
            'status' => OrderStatus::Pending->value,
            'payment_intent_id' => 'pi_failed_test',
        ]);

        $payload = makePaymentEventPayload('payment.failed', 'pi_failed_test');
        $signature = makePayMongoSignature($payload, 'test_webhook_secret');

        $this->call(
            'POST',
            '/api/webhooks/paymongo',
            [],
            [],
            [],
            ['HTTP_PAYMONGO-SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        )->assertOk();

        $fresh = $order->fresh();
        expect($fresh->status)->toBe(OrderStatus::Cancelled->value)
            ->and($fresh->payment_status->value)->toBe(OrderPaymentStatus::Unpaid->value);
    });

    it('returns 200 for unknown event types (graceful ignore)', function () {
        $payload = json_encode(['data' => ['attributes' => ['type' => 'payment.refunded', 'data' => []]]]);
        $signature = makePayMongoSignature($payload, 'test_webhook_secret');

        $this->call(
            'POST',
            '/api/webhooks/paymongo',
            [],
            [],
            [],
            ['HTTP_PAYMONGO-SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        )->assertOk();
    });

    it('returns 200 when order not found for payment intent (logs warning)', function () {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('warning')->once();

        $payload = makePaymentEventPayload('payment.paid', 'pi_nonexistent');
        $signature = makePayMongoSignature($payload, 'test_webhook_secret');

        $this->call(
            'POST',
            '/api/webhooks/paymongo',
            [],
            [],
            [],
            ['HTTP_PAYMONGO-SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        )->assertOk();
    });

});
