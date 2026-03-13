<?php

use App\Models\Property;
use App\Models\RentalAgreement;
use App\Models\Store;
use App\Models\User;
use App\Notifications\LandlordAgreementResponseNotification;
use App\Notifications\RentalAgreementPendingNotification;
use App\Notifications\RentalAgreementQuestionNotification;
use App\Notifications\RentalConfirmedLandlordNotification;
use App\Notifications\RentalConfirmedTenantNotification;
use App\PropertyStatus;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->landlord = User::factory()->create();
    $this->store = Store::factory()->create([
        'user_id' => $this->landlord->id,
        'sector' => 'real_estate',
    ]);
    $this->tenant = User::factory()->create();
    $this->property = Property::factory()->create([
        'store_id' => $this->store->id,
        'status' => PropertyStatus::Active,
        'published_at' => now(),
    ]);
});

test('creating a rental agreement sends pending notification to tenant', function () {
    Notification::fake();

    $agreement = RentalAgreement::create([
        'property_id' => $this->property->id,
        'store_id' => $this->store->id,
        'tenant_name' => 'Nelson Doe',
        'tenant_email' => 'nelson@example.com',
        'monthly_rent' => 2500000,
        'move_in_date' => '2026-04-01',
        'lease_term_months' => 12,
        'status' => 'pending',
    ]);

    Notification::assertSentOnDemand(RentalAgreementPendingNotification::class, function ($notification, $channels, $notifiable) {
        return $notifiable->routes['mail'] === 'nelson@example.com';
    });
});

test('tenant signing agreement updates property status and notifies both parties', function () {
    Notification::fake();

    $agreement = RentalAgreement::factory()->create([
        'property_id' => $this->property->id,
        'store_id' => $this->store->id,
        'tenant_email' => $this->tenant->email,
        'status' => 'pending',
    ]);

    // Simulate tenant signing
    $agreement->update(['status' => 'signed', 'signed_at' => now()]);

    // Check property status
    $this->property->refresh();
    expect($this->property->status)->toBe(PropertyStatus::Rented);

    // Check notifications
    Notification::assertSentOnDemand(RentalConfirmedTenantNotification::class);
    Notification::assertSentTo($this->landlord, RentalConfirmedLandlordNotification::class);
});

test('tenant asking questions notifies landlord', function () {
    Notification::fake();

    $agreement = RentalAgreement::factory()->create([
        'store_id' => $this->store->id,
        'status' => 'pending',
    ]);

    $agreement->update([
        'status' => 'negotiating',
        'tenant_questions' => 'Can I have a pet?',
    ]);

    Notification::assertSentTo($this->landlord, RentalAgreementQuestionNotification::class);
});

test('landlord responding to questions notifies tenant', function () {
    Notification::fake();

    $agreement = RentalAgreement::factory()->create([
        'tenant_email' => 'tenant@example.com',
        'status' => 'negotiating',
        'tenant_questions' => 'Can I have a pet?',
    ]);

    $agreement->update([
        'landlord_response' => 'Only small pets are allowed.',
    ]);

    Notification::assertSentOnDemand(LandlordAgreementResponseNotification::class, function ($notification) {
        return $notification->agreement->landlord_response === 'Only small pets are allowed.';
    });
});
