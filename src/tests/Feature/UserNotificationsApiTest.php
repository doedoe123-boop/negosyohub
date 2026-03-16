<?php

use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\Store;
use App\Models\User;
use App\Notifications\InquiryStatusUpdatedNotification;
use App\UserRole;
use Illuminate\Support\Facades\Mail;

describe('User notifications API', function () {
    it('lists unread notifications and returns unread_count', function () {
        Mail::fake();

        $user = User::factory()->create(['role' => UserRole::Customer]);
        $store = Store::factory()->create();
        $property = Property::factory()->create(['store_id' => $store->id]);
        $inquiry = PropertyInquiry::factory()->contacted()->create([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'store_id' => $store->id,
        ]);
        $user->notify(new InquiryStatusUpdatedNotification($inquiry));

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/notifications')
            ->assertOk()
            ->assertJsonStructure(['notifications', 'unread_count']);

        expect($response->json('unread_count'))->toBe(1)
            ->and($response->json('notifications'))->toHaveCount(1);
    });

    it('marks a notification as read', function () {
        Mail::fake();

        $user = User::factory()->create(['role' => UserRole::Customer]);
        $store = Store::factory()->create();
        $property = Property::factory()->create(['store_id' => $store->id]);
        $inquiry = PropertyInquiry::factory()->contacted()->create([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'store_id' => $store->id,
        ]);
        $user->notify(new InquiryStatusUpdatedNotification($inquiry));
        $notif = $user->unreadNotifications()->first();

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/user/notifications/{$notif->id}/read")
            ->assertOk();

        expect($notif->fresh()->read_at)->not->toBeNull();
    });

    it('marks all notifications as read', function () {
        Mail::fake();

        $user = User::factory()->create(['role' => UserRole::Customer]);
        $store = Store::factory()->create();
        $property = Property::factory()->create(['store_id' => $store->id]);
        $inquiry = PropertyInquiry::factory()->contacted()->create([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'store_id' => $store->id,
        ]);
        $user->notify(new InquiryStatusUpdatedNotification($inquiry));
        $user->notify(new InquiryStatusUpdatedNotification($inquiry));

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/notifications/read-all')
            ->assertOk();

        expect($user->unreadNotifications()->count())->toBe(0);
    });

    it('returns 401 for unauthenticated requests', function () {
        $this->getJson('/api/v1/user/notifications')->assertUnauthorized();
        $this->patchJson('/api/v1/user/notifications/fake-id/read')->assertUnauthorized();
        $this->postJson('/api/v1/user/notifications/read-all')->assertUnauthorized();
    });
});

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
