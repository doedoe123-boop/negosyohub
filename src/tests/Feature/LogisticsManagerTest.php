<?php

use App\DeliveryStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Store;
use App\Services\LogisticsManager;
use App\Services\Webhooks\WebhookEventDispatcher;
use App\ShipmentProvider;

it('creates a shipment and dispatches a shipment created webhook payload', function () {
    $store = Store::factory()->create([
        'address' => ['line1' => '123 Seller Street'],
    ]);
    $order = Order::factory()->create([
        'store_id' => $store->id,
    ]);

    $dispatcher = Mockery::mock(WebhookEventDispatcher::class);
    $dispatcher->shouldReceive('dispatchForOrder')
        ->once()
        ->withArgs(function (Order $dispatchedOrder, string $event, array $payload) use ($order): bool {
            return $dispatchedOrder->is($order)
                && $event === 'shipment.created'
                && $payload['provider'] === 'lalamove'
                && $payload['delivery_status'] === 'driver_assigned'
                && $payload['tracking_url'] === 'https://tracking.test/shipments/123';
        });

    $manager = new LogisticsManager($dispatcher);

    $shipment = $manager->upsertShipment($order, [
        'provider' => ShipmentProvider::Lalamove->value,
        'delivery_status' => DeliveryStatus::DriverAssigned->value,
        'driver_name' => 'Juan Rider',
        'driver_contact' => '09171234567',
        'tracking_url' => 'https://tracking.test/shipments/123',
    ]);

    expect($shipment)->toBeInstanceOf(Shipment::class)
        ->and($shipment->provider)->toBe(ShipmentProvider::Lalamove)
        ->and($shipment->delivery_status)->toBe(DeliveryStatus::DriverAssigned)
        ->and($shipment->driver_name)->toBe('Juan Rider')
        ->and($shipment->pickup_address)->toBe('123 Seller Street');
});

it('uses the in-app tracking route for demo tracking hosts', function () {
    $order = Order::factory()->create();
    $shipment = Shipment::factory()->create([
        'order_id' => $order->id,
        'tracking_url' => 'https://tracking.negosyohub.test/DEMO-COD-TECHNEST',
    ]);

    $dispatcher = Mockery::mock(WebhookEventDispatcher::class);
    $manager = new LogisticsManager($dispatcher);

    $payload = $manager->shipmentPayload($shipment);

    expect($payload['tracking_url'])->toBe("/account/orders/{$order->id}/tracking");
});

it('updates shipment delivery status timestamps and dispatches an update webhook payload', function () {
    $order = Order::factory()->create();
    $shipment = Shipment::factory()->create([
        'order_id' => $order->id,
        'delivery_status' => DeliveryStatus::DriverAssigned,
        'delivered_at' => null,
    ]);

    $dispatcher = Mockery::mock(WebhookEventDispatcher::class);
    $dispatcher->shouldReceive('dispatchForOrder')
        ->once()
        ->withArgs(function (Order $dispatchedOrder, string $event, array $payload) use ($order): bool {
            return $dispatchedOrder->is($order)
                && $event === 'shipment.updated'
                && $payload['delivery_status'] === 'delivered'
                && $payload['customer_delivery_label'] === 'Delivered';
        });

    $manager = new LogisticsManager($dispatcher);
    $updatedShipment = $manager->updateStatus($shipment, DeliveryStatus::Delivered);

    expect($updatedShipment->delivery_status)->toBe(DeliveryStatus::Delivered)
        ->and($updatedShipment->delivered_at)->not->toBeNull()
        ->and($updatedShipment->failed_at)->toBeNull();
});
