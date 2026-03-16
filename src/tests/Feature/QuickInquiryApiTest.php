<?php

use App\InquiryStatus;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\Store;
use App\Models\User;
use App\PropertyStatus;
use App\UserRole;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

describe('POST /api/v1/properties/{slug}/quick-inquiry', function () {
    it('creates an inquiry for authenticated user', function () {
        Mail::fake();
        Notification::fake();

        $store = Store::factory()->create(['sector' => 'real_estate']);
        $property = Property::factory()->create([
            'store_id' => $store->id,
            'status' => PropertyStatus::Active,
            'published_at' => now(),
        ]);
        $user = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/properties/{$property->slug}/quick-inquiry");

        $response->assertCreated()
            ->assertJsonStructure(['message', 'id']);

        $this->assertDatabaseHas('property_inquiries', [
            'property_id' => $property->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => InquiryStatus::New->value,
        ]);
    });

    it('auto-generates a default message when none is provided', function () {
        Mail::fake();
        Notification::fake();

        $store = Store::factory()->create(['sector' => 'real_estate']);
        $property = Property::factory()->create([
            'store_id' => $store->id,
            'status' => PropertyStatus::Active,
            'published_at' => now(),
            'title' => 'Sunset Villa',
        ]);
        $user = User::factory()->create(['role' => UserRole::Customer, 'name' => 'Maria Santos']);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/properties/{$property->slug}/quick-inquiry")
            ->assertCreated();

        $inquiry = PropertyInquiry::latest()->first();
        expect($inquiry->message)->toContain('Maria Santos')
            ->and($inquiry->message)->toContain('Sunset Villa');
    });

    it('uses custom message when provided', function () {
        Mail::fake();
        Notification::fake();

        $store = Store::factory()->create(['sector' => 'real_estate']);
        $property = Property::factory()->create([
            'store_id' => $store->id,
            'status' => PropertyStatus::Active,
            'published_at' => now(),
        ]);
        $user = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/properties/{$property->slug}/quick-inquiry", [
                'message' => 'I would like to schedule a viewing this weekend.',
            ])
            ->assertCreated();

        $inquiry = PropertyInquiry::latest()->first();
        expect($inquiry->message)->toBe('I would like to schedule a viewing this weekend.');
    });

    it('rejects message exceeding 1000 chars', function () {
        $store = Store::factory()->create(['sector' => 'real_estate']);
        $property = Property::factory()->create([
            'store_id' => $store->id,
            'status' => PropertyStatus::Active,
            'published_at' => now(),
        ]);
        $user = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/properties/{$property->slug}/quick-inquiry", [
                'message' => str_repeat('a', 1001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');
    });

    it('returns 401 for unauthenticated users', function () {
        $store = Store::factory()->create(['sector' => 'real_estate']);
        $property = Property::factory()->create([
            'store_id' => $store->id,
            'status' => PropertyStatus::Active,
            'published_at' => now(),
        ]);

        $this->postJson("/api/v1/properties/{$property->slug}/quick-inquiry")
            ->assertUnauthorized();
    });

    it('returns 404 for inactive property', function () {
        Mail::fake();
        Notification::fake();

        $store = Store::factory()->create(['sector' => 'real_estate']);
        $property = Property::factory()->create([
            'store_id' => $store->id,
            'status' => PropertyStatus::Draft,
        ]);
        $user = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/properties/{$property->slug}/quick-inquiry")
            ->assertNotFound();
    });

    it('response says landlord for paupahan stores', function () {
        Mail::fake();
        Notification::fake();

        \App\Models\Sector::factory()->create([
            'slug' => 'paupahan',
            'template' => \App\SectorTemplate::Rental,
        ]);
        $store = Store::factory()->create(['sector' => 'paupahan']);
        $property = Property::factory()->create([
            'store_id' => $store->id,
            'status' => PropertyStatus::Active,
            'published_at' => now(),
        ]);
        $user = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/properties/{$property->slug}/quick-inquiry");

        $response->assertCreated();
        expect($response->json('message'))->toContain('landlord');
    });

    it('response says agent for real estate stores', function () {
        Mail::fake();
        Notification::fake();

        \App\Models\Sector::factory()->create([
            'slug' => 'real_estate',
            'template' => \App\SectorTemplate::RealEstate,
        ]);
        $store = Store::factory()->create(['sector' => 'real_estate']);
        $property = Property::factory()->create([
            'store_id' => $store->id,
            'status' => PropertyStatus::Active,
            'published_at' => now(),
        ]);
        $user = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/properties/{$property->slug}/quick-inquiry");

        $response->assertCreated();
        expect($response->json('message'))->toContain('agent');
    });
});

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
