<?php

namespace App\Jobs;

use App\Models\WebhookEndpoint;
use App\WebhookDeliveryStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class DeliverWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public WebhookEndpoint $endpoint,
        public string $event,
        public array $payload
    ) {}

    public function handle(): void
    {
        $signature = $this->endpoint->secret
            ? hash_hmac('sha256', json_encode($this->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $this->endpoint->secret)
            : null;

        $delivery = $this->endpoint->deliveries()->create([
            'event' => $this->event,
            'delivery_status' => WebhookDeliveryStatus::Pending->value,
            'signature' => $signature,
            'payload' => $this->payload,
            'attempts' => 0,
        ]);

        $response = Http::timeout(10)
            ->acceptJson()
            ->withHeaders(array_filter([
                'X-NegosyoHub-Event' => $this->event,
                'X-NegosyoHub-Signature' => $signature,
            ]))
            ->post($this->endpoint->url, $this->payload);

        $delivery->update([
            'attempts' => 1,
            'response_status' => $response->status(),
            'response_body' => $response->body(),
            'delivery_status' => $response->successful()
                ? WebhookDeliveryStatus::Delivered->value
                : WebhookDeliveryStatus::Failed->value,
            'delivered_at' => $response->successful() ? now() : null,
            'failed_at' => $response->successful() ? null : now(),
        ]);

        if ($response->successful()) {
            $this->endpoint->update(['last_delivered_at' => now()]);

            return;
        }

        throw new \RuntimeException('Webhook delivery failed with status '.$response->status());
    }
}
