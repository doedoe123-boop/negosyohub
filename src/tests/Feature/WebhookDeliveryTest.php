<?php

use App\Jobs\DeliverWebhookJob;
use App\Models\Order;
use App\Models\Store;
use App\Models\WebhookEndpoint;
use App\Services\Webhooks\WebhookEventDispatcher;
use App\WebhookDeliveryStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Lunar\Models\Currency;

beforeEach(function () {
    Currency::factory()->create([
        'default' => true,
        'code' => 'PHP',
    ]);
});

it('dispatches webhook jobs only for active matching global and store endpoints', function () {
    Queue::fake();

    $store = Store::factory()->create();
    $otherStore = Store::factory()->create();
    $order = Order::factory()->create([
        'store_id' => $store->id,
    ]);

    $globalEndpoint = WebhookEndpoint::factory()->create([
        'store_id' => null,
        'events' => ['order.created'],
        'is_active' => true,
    ]);

    $storeEndpoint = WebhookEndpoint::factory()->create([
        'store_id' => $store->id,
        'events' => ['order.created'],
        'is_active' => true,
    ]);

    WebhookEndpoint::factory()->create([
        'store_id' => $otherStore->id,
        'events' => ['order.created'],
        'is_active' => true,
    ]);

    WebhookEndpoint::factory()->create([
        'store_id' => $store->id,
        'events' => ['payment.paid'],
        'is_active' => true,
    ]);

    WebhookEndpoint::factory()->create([
        'store_id' => $store->id,
        'events' => ['order.created'],
        'is_active' => false,
    ]);

    app(WebhookEventDispatcher::class)->dispatchForOrder($order, 'order.created');

    Queue::assertPushed(DeliverWebhookJob::class, 2);
    Queue::assertPushed(DeliverWebhookJob::class, function (DeliverWebhookJob $job) use ($globalEndpoint): bool {
        return $job->endpoint->is($globalEndpoint) && $job->event === 'order.created';
    });
    Queue::assertPushed(DeliverWebhookJob::class, function (DeliverWebhookJob $job) use ($storeEndpoint): bool {
        return $job->endpoint->is($storeEndpoint) && $job->event === 'order.created';
    });
});

it('records successful webhook deliveries with a signature', function () {
    Http::fake([
        'https://webhooks.example.test/*' => Http::response(['ok' => true], 200),
    ]);

    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://webhooks.example.test/orders',
        'secret' => 'secret-key',
    ]);

    $job = new DeliverWebhookJob($endpoint, 'order.created', [
        'event' => 'order.created',
        'order' => ['id' => 123],
    ]);

    $job->handle();

    $delivery = $endpoint->fresh()->deliveries()->latest()->first();

    expect($delivery)->not->toBeNull()
        ->and($delivery->delivery_status)->toBe(WebhookDeliveryStatus::Delivered)
        ->and($delivery->response_status)->toBe(200)
        ->and($delivery->signature)->not->toBeNull()
        ->and($endpoint->fresh()->last_delivered_at)->not->toBeNull();
});

it('marks failed webhook deliveries and throws an exception', function () {
    Http::fake([
        'https://webhooks.example.test/*' => Http::response(['error' => 'fail'], 500),
    ]);

    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://webhooks.example.test/orders',
    ]);

    $job = new DeliverWebhookJob($endpoint, 'shipment.updated', [
        'event' => 'shipment.updated',
        'shipment' => ['id' => 55],
    ]);

    expect(fn () => $job->handle())
        ->toThrow(RuntimeException::class, 'Webhook delivery failed with status 500');

    $delivery = $endpoint->fresh()->deliveries()->latest()->first();

    expect($delivery)->not->toBeNull()
        ->and($delivery->delivery_status)->toBe(WebhookDeliveryStatus::Failed)
        ->and($delivery->response_status)->toBe(500)
        ->and($delivery->failed_at)->not->toBeNull();
});
