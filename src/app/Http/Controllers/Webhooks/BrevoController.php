<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BrevoController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        if (! $this->hasValidSecret($request)) {
            Log::warning('Brevo webhook: invalid secret.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->json()->all();
        $events = array_is_list($payload) ? $payload : [$payload];

        foreach ($events as $eventPayload) {
            $this->handleEvent($eventPayload);
        }

        return response()->json(['message' => 'OK']);
    }

    private function hasValidSecret(Request $request): bool
    {
        $configured = (string) config('services.brevo.webhook_secret');

        if ($configured === '') {
            return false;
        }

        return hash_equals($configured, (string) $request->header('X-Brevo-Webhook-Secret'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleEvent(array $payload): void
    {
        $email = strtolower((string) ($payload['email'] ?? ''));
        $event = (string) ($payload['event'] ?? '');

        if ($email === '' || $event === '') {
            Log::warning('Brevo webhook: missing email or event.', [
                'payload' => $payload,
            ]);

            return;
        }

        $subscriber = NewsletterSubscriber::query()
            ->where('email', $email)
            ->first();

        if (! $subscriber) {
            Log::warning('Brevo webhook: subscriber not found.', [
                'email' => $email,
                'event' => $event,
            ]);

            return;
        }

        $eventAt = $this->resolveEventTimestamp($payload);

        $updates = [
            'last_brevo_event' => $event,
        ];

        match ($event) {
            'request', 'sent' => $updates += [
                'welcome_email_status' => 'sent',
                'welcome_sent_at' => $eventAt,
                'last_brevo_error' => null,
            ],
            'delivered' => $updates += [
                'welcome_email_status' => 'delivered',
                'welcome_delivered_at' => $eventAt,
                'last_brevo_error' => null,
            ],
            'opened', 'uniqueOpened' => $updates += [
                'welcome_email_status' => 'opened',
                'welcome_opened_at' => $eventAt,
                'last_brevo_error' => null,
            ],
            'hardBounce', 'softBounce', 'invalid', 'blocked', 'error', 'spam' => $updates += [
                'welcome_email_status' => 'failed',
                'welcome_bounced_at' => $eventAt,
                'last_brevo_error' => (string) ($payload['reason'] ?? $payload['message'] ?? $event),
            ],
            'unsubscribed' => $updates += [
                'welcome_email_status' => 'unsubscribed',
                'last_brevo_error' => null,
            ],
            default => [],
        };

        $subscriber->update($updates);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveEventTimestamp(array $payload): ?Carbon
    {
        $candidates = [
            $payload['date_event'] ?? null,
            $payload['date'] ?? null,
            isset($payload['ts_event']) ? Carbon::createFromTimestamp((int) $payload['ts_event']) : null,
            isset($payload['ts']) ? Carbon::createFromTimestamp((int) $payload['ts']) : null,
            isset($payload['ts_epoch']) ? Carbon::createFromTimestamp((int) $payload['ts_epoch']) : null,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate instanceof Carbon) {
                return $candidate;
            }

            if (is_string($candidate) && $candidate !== '') {
                try {
                    return Carbon::parse($candidate);
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return now();
    }
}
