<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class BrevoNewsletterService
{
    public function enabled(): bool
    {
        return filled(config('services.brevo.api_key'))
            && filled(config('services.brevo.newsletter_list_id'));
    }

    public function subscribe(string $email, ?string $source = null): bool
    {
        if (! $this->enabled()) {
            Log::warning('Brevo newsletter sync skipped because configuration is incomplete.', [
                'email' => strtolower($email),
                'source' => $source,
                'has_api_key' => filled(config('services.brevo.api_key')),
                'has_list_id' => filled(config('services.brevo.newsletter_list_id')),
            ]);

            return false;
        }

        try {
            $response = Http::baseUrl((string) config('services.brevo.base_url', 'https://api.brevo.com/v3'))
                ->withHeaders([
                    'api-key' => (string) config('services.brevo.api_key'),
                    'accept' => 'application/json',
                ])
                ->timeout(10)
                ->post('/contacts', [
                    'email' => strtolower($email),
                    'listIds' => [
                        (int) config('services.brevo.newsletter_list_id'),
                    ],
                    'updateEnabled' => true,
                    'attributes' => array_filter([
                        'SOURCE' => $source,
                    ]),
                ]);

            if ($response->successful()) {
                Log::info('Brevo newsletter sync succeeded.', [
                    'email' => strtolower($email),
                    'source' => $source,
                    'list_id' => (int) config('services.brevo.newsletter_list_id'),
                    'status' => $response->status(),
                    'brevo_contact_id' => $response->json('id'),
                ]);

                return true;
            }

            Log::warning('Brevo newsletter sync failed.', [
                'email' => strtolower($email),
                'source' => $source,
                'list_id' => (int) config('services.brevo.newsletter_list_id'),
                'status' => $response->status(),
                'response' => $response->json() ?: $response->body(),
            ]);

            return $response->successful();
        } catch (Throwable $exception) {
            Log::error('Brevo newsletter sync threw an exception.', [
                'email' => strtolower($email),
                'source' => $source,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
