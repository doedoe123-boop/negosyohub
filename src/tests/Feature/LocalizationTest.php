<?php

use App\Models\Translation;
use App\Models\User;
use Lunar\Models\Language;

it('returns lang file messages when no database override exists', function () {
    Language::query()->create([
        'name' => 'English',
        'code' => 'en',
        'default' => true,
        'is_active' => true,
    ]);

    Language::query()->create([
        'name' => 'Filipino',
        'code' => 'fil',
        'default' => false,
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/v1/localization?locale=fil');

    $response
        ->assertOk()
        ->assertJsonPath('locale', 'fil')
        ->assertJsonPath('messages.nav.signIn', 'Mag-sign in')
        ->assertJsonPath('messages.checkout.orderSummary', 'Buod ng Order');
});

it('prefers database overrides over lang files', function () {
    Language::query()->create([
        'name' => 'English',
        'code' => 'en',
        'default' => true,
        'is_active' => true,
    ]);

    Translation::query()->create([
        'locale' => 'en',
        'key' => 'checkout.orderSummary',
        'value' => 'Custom Checkout Summary',
    ]);

    $response = $this->getJson('/api/v1/localization?locale=en');

    $response
        ->assertOk()
        ->assertJsonPath('messages.checkout.orderSummary', 'Custom Checkout Summary');
});

it('updates the authenticated users preferred locale', function () {
    Language::query()->create([
        'name' => 'English',
        'code' => 'en',
        'default' => true,
        'is_active' => true,
    ]);

    Language::query()->create([
        'name' => 'Filipino',
        'code' => 'fil',
        'default' => false,
        'is_active' => true,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson('/api/v1/user/settings', [
            'preferred_locale' => 'fil',
            'notification_preferences' => [
                'order_updates' => true,
                'promotions' => false,
            ],
        ])
        ->assertOk()
        ->assertJsonPath('preferred_locale', 'fil');

    expect($user->fresh()->preferred_locale)->toBe('fil');
});
