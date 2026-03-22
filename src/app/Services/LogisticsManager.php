<?php

namespace App\Services;

use App\DeliveryStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Webhooks\WebhookEventDispatcher;
use App\ShipmentProvider;

class LogisticsManager
{
    public function __construct(
        private WebhookEventDispatcher $webhookEventDispatcher
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function upsertShipment(Order $order, array $attributes): Shipment
    {
        $shipment = $order->shipments()->latest()->first() ?? new Shipment([
            'order_id' => $order->id,
        ]);

        $shipment->fill([
            'provider' => $attributes['provider'] ?? ShipmentProvider::Manual->value,
            'external_reference' => $attributes['external_reference'] ?? $shipment->external_reference,
            'delivery_status' => $attributes['delivery_status'] ?? $shipment->delivery_status ?? DeliveryStatus::AwaitingBooking->value,
            'driver_name' => $attributes['driver_name'] ?? $shipment->driver_name,
            'driver_contact' => $attributes['driver_contact'] ?? $shipment->driver_contact,
            'vehicle_type' => $attributes['vehicle_type'] ?? $shipment->vehicle_type,
            'tracking_url' => $attributes['tracking_url'] ?? $shipment->tracking_url,
            'pickup_address' => $attributes['pickup_address'] ?? $shipment->pickup_address ?? $this->resolvePickupAddress($order),
            'dropoff_address' => $attributes['dropoff_address'] ?? $shipment->dropoff_address ?? $this->resolveDropoffAddress($order),
            'booking_payload' => $attributes['booking_payload'] ?? $shipment->booking_payload,
            'provider_response' => $attributes['provider_response'] ?? $shipment->provider_response,
            'booked_at' => $attributes['booked_at'] ?? $shipment->booked_at ?? now(),
            'last_synced_at' => now(),
        ]);

        $shipment->save();

        $this->webhookEventDispatcher->dispatchForOrder(
            $order,
            'shipment.created',
            $this->shipmentPayload($shipment)
        );

        return $shipment->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateStatus(Shipment $shipment, DeliveryStatus $status, array $attributes = []): Shipment
    {
        $updates = [
            'delivery_status' => $status->value,
            'last_synced_at' => now(),
        ];

        if ($status === DeliveryStatus::PickedUp) {
            $updates['picked_up_at'] = $attributes['picked_up_at'] ?? now();
        }

        if ($status === DeliveryStatus::Delivered) {
            $updates['delivered_at'] = $attributes['delivered_at'] ?? now();
        }

        if ($status === DeliveryStatus::Failed) {
            $updates['failed_at'] = $attributes['failed_at'] ?? now();
        }

        if ($status === DeliveryStatus::Cancelled) {
            $updates['cancelled_at'] = $attributes['cancelled_at'] ?? now();
        }

        $shipment->update(array_merge($updates, $attributes));

        $this->webhookEventDispatcher->dispatchForOrder(
            $shipment->order,
            'shipment.updated',
            $this->shipmentPayload($shipment->refresh())
        );

        return $shipment->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function shipmentPayload(Shipment $shipment): array
    {
        return [
            'id' => $shipment->id,
            'order_id' => $shipment->order_id,
            'provider' => $shipment->provider?->value,
            'provider_label' => $shipment->provider?->label(),
            'external_reference' => $shipment->external_reference,
            'delivery_status' => $shipment->delivery_status?->value,
            'delivery_status_label' => $shipment->delivery_status?->label(),
            'customer_delivery_label' => $shipment->delivery_status?->customerLabel(),
            'driver_name' => $shipment->driver_name,
            'driver_contact' => $shipment->driver_contact,
            'vehicle_type' => $shipment->vehicle_type,
            'tracking_url' => $shipment->tracking_url,
            'pickup_address' => $shipment->pickup_address,
            'dropoff_address' => $shipment->dropoff_address,
            'booked_at' => $shipment->booked_at,
            'picked_up_at' => $shipment->picked_up_at,
            'delivered_at' => $shipment->delivered_at,
            'failed_at' => $shipment->failed_at,
        ];
    }

    private function resolvePickupAddress(Order $order): ?string
    {
        return $order->store?->address['line1']
            ?? $order->store?->address['address_line']
            ?? $order->store?->address['street']
            ?? $order->store?->name;
    }

    private function resolveDropoffAddress(Order $order): ?string
    {
        $address = $order->shippingAddress;

        if (! $address) {
            return null;
        }

        return collect([
            $address->line_one,
            $address->line_two,
            $address->city,
            $address->state,
            $address->postcode,
        ])->filter()->implode(', ');
    }
}
