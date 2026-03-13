<?php

use App\Models\Property;
use App\Models\RentalAgreement;
use App\Models\User;
use App\Notifications\RentalAgreementQuestionNotification;
use App\Notifications\RentalConfirmedTenantNotification;
use App\PropertyStatus;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->property = Property::factory()->create([
        'status' => PropertyStatus::Active,
        'published_at' => now(),
    ]);
});

test('user can list their own rental agreements', function () {
    RentalAgreement::factory()->count(3)->create([
        'tenant_user_id' => $this->user->id,
    ]);

    // Another user's agreement
    RentalAgreement::factory()->create([
        'tenant_user_id' => User::factory()->create()->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/user/rental-agreements');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('user can sign a rental agreement', function () {
    Notification::fake();

    $agreement = RentalAgreement::factory()->create([
        'tenant_user_id' => $this->user->id,
        'property_id' => $this->property->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/v1/user/rental-agreements/{$agreement->id}", [
            'status' => 'signed',
        ]);

    $response->assertStatus(200);

    $agreement->refresh();
    expect($agreement->status)->toBe('signed')
        ->and($agreement->signed_at)->not->toBeNull();

    // Verify property status updated
    $this->property->refresh();
    expect($this->property->status)->toBe(PropertyStatus::Rented);

    // Verify confirmed notification sent
    Notification::assertSentOnDemand(RentalConfirmedTenantNotification::class);
});

test('user can submit questions for a rental agreement', function () {
    Notification::fake();

    $agreement = RentalAgreement::factory()->create([
        'tenant_user_id' => $this->user->id,
        'status' => 'pending',
    ]);

    $questions = 'Can I change the paint color?';

    $response = $this->actingAs($this->user)
        ->patchJson("/api/v1/user/rental-agreements/{$agreement->id}", [
            'status' => 'negotiating',
            'tenant_questions' => $questions,
        ]);

    $response->assertStatus(200);

    $agreement->refresh();
    expect($agreement->status)->toBe('negotiating')
        ->and($agreement->tenant_questions)->toBe($questions);

    // Verify landlord (via store owner) received notification
    $landlord = $agreement->store->owner;
    Notification::assertSentTo($landlord, RentalAgreementQuestionNotification::class);
});

test('user cannot update someone else rental agreement', function () {
    $otherUser = User::factory()->create();
    $agreement = RentalAgreement::factory()->create([
        'tenant_user_id' => $otherUser->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/v1/user/rental-agreements/{$agreement->id}", [
            'status' => 'signed',
        ]);

    $response->assertStatus(404);
});
