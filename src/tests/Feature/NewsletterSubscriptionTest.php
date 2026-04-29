<?php

use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Http;

it('stores a newsletter subscriber from the public deals form', function () {
    config([
        'services.brevo.api_key' => 'brevo-test-key',
        'services.brevo.newsletter_list_id' => 42,
    ]);

    Http::fake([
        'https://api.brevo.com/v3/contacts' => Http::response(['id' => 123], 201),
    ]);

    $this->from(route('deals.index'))
        ->post(route('newsletter.subscribe'), [
            'email' => 'merchant@example.com',
            'source' => 'deals.index',
        ])
        ->assertRedirect(route('deals.index'))
        ->assertSessionHas('newsletter_status');

    $this->assertDatabaseHas('newsletter_subscribers', [
        'email' => 'merchant@example.com',
        'source' => 'deals.index',
    ]);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.brevo.com/v3/contacts'
            && $request['email'] === 'merchant@example.com'
            && $request['listIds'] === [42]
            && $request['updateEnabled'] === true;
    });
});

it('does not duplicate an existing newsletter subscriber', function () {
    config([
        'services.brevo.api_key' => 'brevo-test-key',
        'services.brevo.newsletter_list_id' => 42,
    ]);

    Http::fake([
        'https://api.brevo.com/v3/contacts' => Http::response(['id' => 123], 201),
    ]);

    NewsletterSubscriber::query()->create([
        'email' => 'merchant@example.com',
        'source' => 'homepage',
        'subscribed_at' => now(),
    ]);

    $this->post(route('newsletter.subscribe'), [
        'email' => 'merchant@example.com',
        'source' => 'deals.index',
    ])->assertSessionHas('newsletter_status');

    expect(NewsletterSubscriber::query()->where('email', 'merchant@example.com')->count())
        ->toBe(1);
});

it('still stores the subscriber when Brevo is unavailable', function () {
    config([
        'services.brevo.api_key' => 'brevo-test-key',
        'services.brevo.newsletter_list_id' => 42,
    ]);

    Http::fake([
        'https://api.brevo.com/v3/contacts' => Http::response([], 500),
    ]);

    $this->post(route('newsletter.subscribe'), [
        'email' => 'fallback@example.com',
        'source' => 'deals.index',
    ])->assertSessionHas('newsletter_status');

    $this->assertDatabaseHas('newsletter_subscribers', [
        'email' => 'fallback@example.com',
        'source' => 'deals.index',
    ]);
});
