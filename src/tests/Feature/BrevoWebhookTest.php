<?php

use App\Models\NewsletterSubscriber;

it('updates subscriber welcome email status from a delivered Brevo webhook event', function () {
    config([
        'services.brevo.webhook_secret' => 'brevo-secret',
    ]);

    $subscriber = NewsletterSubscriber::query()->create([
        'email' => 'merchant@example.com',
        'source' => 'deals.index',
        'subscribed_at' => now(),
        'brevo_sync_status' => 'synced',
        'welcome_email_status' => 'pending',
    ]);

    $this->postJson(route('webhooks.brevo'), [
        'event' => 'delivered',
        'email' => 'merchant@example.com',
        'ts_event' => now()->timestamp,
    ], [
        'X-Brevo-Webhook-Secret' => 'brevo-secret',
    ])->assertOk();

    $subscriber->refresh();

    expect($subscriber->welcome_email_status)->toBe('delivered')
        ->and($subscriber->welcome_delivered_at)->not->toBeNull()
        ->and($subscriber->last_brevo_event)->toBe('delivered');
});

it('rejects Brevo webhooks with an invalid secret', function () {
    config([
        'services.brevo.webhook_secret' => 'brevo-secret',
    ]);

    $this->postJson(route('webhooks.brevo'), [
        'event' => 'delivered',
        'email' => 'merchant@example.com',
    ], [
        'X-Brevo-Webhook-Secret' => 'wrong-secret',
    ])->assertUnauthorized();
});
