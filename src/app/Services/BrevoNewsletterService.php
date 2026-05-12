<?php

namespace App\Services;

use App\Models\NewsletterSubscriber;
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

    public function subscribe(NewsletterSubscriber $subscriber): bool
    {
        $email = strtolower($subscriber->email);
        $source = $subscriber->source;

        if (! $this->enabled()) {
            $subscriber->forceFill([
                'brevo_sync_status' => 'skipped',
                'last_brevo_error' => 'Brevo configuration is incomplete.',
            ])->save();

            Log::warning('Brevo newsletter sync skipped because configuration is incomplete.', [
                'email' => $email,
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
                    'content-type' => 'application/json',
                ])
                ->timeout(10)
                ->post('/contacts', [
                    'email' => $email,
                    'listIds' => [
                        (int) config('services.brevo.newsletter_list_id'),
                    ],
                    'updateEnabled' => true,
                    'attributes' => array_filter([
                        'SOURCE' => $source,
                    ]),
                ]);

            if ($response->successful()) {
                $subscriber->forceFill([
                    'brevo_sync_status' => 'synced',
                    'brevo_synced_at' => now(),
                    'brevo_contact_id' => $response->json('id') ?: $subscriber->brevo_contact_id,
                    'welcome_email_status' => $subscriber->welcome_email_status ?: 'pending',
                    'last_brevo_error' => null,
                ])->save();

                Log::info('Brevo newsletter sync succeeded.', [
                    'email' => $email,
                    'source' => $source,
                    'list_id' => (int) config('services.brevo.newsletter_list_id'),
                    'status' => $response->status(),
                    'brevo_contact_id' => $response->json('id'),
                ]);

                return true;
            }

            $subscriber->forceFill([
                'brevo_sync_status' => 'failed',
                'last_brevo_error' => is_array($response->json())
                    ? json_encode($response->json(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    : $response->body(),
            ])->save();

            Log::warning('Brevo newsletter sync failed.', [
                'email' => $email,
                'source' => $source,
                'list_id' => (int) config('services.brevo.newsletter_list_id'),
                'status' => $response->status(),
                'response' => $response->json() ?: $response->body(),
            ]);

            return $response->successful();
        } catch (Throwable $exception) {
            $subscriber->forceFill([
                'brevo_sync_status' => 'failed',
                'last_brevo_error' => $exception->getMessage(),
            ])->save();

            Log::error('Brevo newsletter sync threw an exception.', [
                'email' => $email,
                'source' => $source,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function resendWelcomeEmail(NewsletterSubscriber $subscriber): bool
    {
        $resendListId = config('services.brevo.newsletter_resend_list_id');

        if (! $this->enabled() || blank($resendListId)) {
            $subscriber->forceFill([
                'last_brevo_error' => 'Brevo resend list is not configured.',
            ])->save();

            return false;
        }

        try {
            $response = Http::baseUrl((string) config('services.brevo.base_url', 'https://api.brevo.com/v3'))
                ->withHeaders([
                    'api-key' => (string) config('services.brevo.api_key'),
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                ])
                ->timeout(10)
                ->post('/contacts/lists/'.$resendListId.'/contacts/add', [
                    'emails' => [$subscriber->email],
                ]);

            if ($response->successful()) {
                $subscriber->forceFill([
                    'welcome_email_status' => 'resent',
                    'welcome_resend_requested_at' => now(),
                    'last_brevo_error' => null,
                ])->save();

                return true;
            }

            $subscriber->forceFill([
                'last_brevo_error' => is_array($response->json())
                    ? json_encode($response->json(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    : $response->body(),
            ])->save();

            return false;
        } catch (Throwable $exception) {
            $subscriber->forceFill([
                'last_brevo_error' => $exception->getMessage(),
            ])->save();

            return false;
        }
    }
}
