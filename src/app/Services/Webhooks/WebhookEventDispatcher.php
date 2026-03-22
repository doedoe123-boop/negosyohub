<?php

namespace App\Services\Webhooks;

use App\Jobs\DeliverWebhookJob;
use App\Models\Order;
use App\Models\Store;
use App\Models\WebhookEndpoint;

class WebhookEventDispatcher
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $event, array $payload, ?Store $store = null): void
    {
        WebhookEndpoint::query()
            ->where('is_active', true)
            ->where(function ($query) use ($store): void {
                $query->whereNull('store_id');

                if ($store) {
                    $query->orWhere('store_id', $store->id);
                }
            })
            ->get()
            ->filter(function (WebhookEndpoint $endpoint) use ($event): bool {
                $events = $endpoint->events ?? [];

                return empty($events) || in_array($event, $events, true);
            })
            ->each(function (WebhookEndpoint $endpoint) use ($event, $payload): void {
                DeliverWebhookJob::dispatch($endpoint, $event, $payload);
            });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatchForOrder(Order $order, string $event, array $payload = []): void
    {
        $this->dispatch($event, [
            'event' => $event,
            'occurred_at' => now()->toIso8601String(),
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'payment_method' => $order->payment_method?->value,
                'payment_status' => $order->payment_status?->value,
                'total' => $order->total?->value,
                'store_id' => $order->store_id,
                'customer_id' => $order->user_id,
            ],
            'data' => $payload,
        ], $order->store);
    }
}
