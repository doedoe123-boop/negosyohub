<?php

use App\InquiryStatus;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\Store;
use App\Models\User;
use App\Notifications\InquiryStatusUpdatedNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

describe('InquiryStatusUpdatedNotification', function () {
    it('database payload contains expected fields', function () {
        $property = Property::factory()->create(['title' => 'Sunset Villa']);
        $inquiry = PropertyInquiry::factory()->create([
            'property_id' => $property->id,
            'status' => InquiryStatus::Contacted,
        ]);
        $notif = new InquiryStatusUpdatedNotification($inquiry);
        $payload = $notif->toDatabase($inquiry->user ?? (object) []);
        expect($payload)->toHaveKey('title')
            ->and($payload)->toHaveKey('body')
            ->and($payload)->toHaveKey('icon')
            ->and($payload)->toHaveKey('icon_color')
            ->and($payload)->toHaveKey('property_slug')
            ->and($payload)->toHaveKey('inquiry_id');
    });

    it('body is status-specific', function () {
        $property = Property::factory()->create(['title' => 'Sunset Villa']);
        $statuses = [
            InquiryStatus::Contacted,
            InquiryStatus::ViewingScheduled,
            InquiryStatus::Negotiating,
            InquiryStatus::Closed,
        ];
        foreach ($statuses as $status) {
            $inquiry = PropertyInquiry::factory()->create([
                'property_id' => $property->id,
                'status' => $status,
            ]);
            $notif = new InquiryStatusUpdatedNotification($inquiry);
            $body = $notif->toDatabase($inquiry->user ?? (object) [])['body'];
            expect($body)->toContain('Sunset Villa');
        }
    });

    it('body falls back to generic for unknown status', function () {
        $property = Property::factory()->create(['title' => 'Sunset Villa']);
        $inquiry = PropertyInquiry::factory()->create([
            'property_id' => $property->id,
            'status' => InquiryStatus::New,
        ]);
        $notif = new InquiryStatusUpdatedNotification($inquiry);
        $body = $notif->toDatabase($inquiry->user ?? (object) [])['body'];
        expect($body)->toContain('updated to New');
    });

    it('only uses the database channel', function () {
        $inquiry = PropertyInquiry::factory()->create();
        $notif = new InquiryStatusUpdatedNotification($inquiry);

        expect($notif->via(new \stdClass))->toBe(['database']);
    });

    it('dispatches to the user when inquiry status changes', function () {
        Notification::fake();
        Mail::fake();

        $user = User::factory()->create();
        $store = Store::factory()->create();
        $property = Property::factory()->create(['store_id' => $store->id]);
        $inquiry = PropertyInquiry::factory()->create([
            'store_id' => $store->id,
            'property_id' => $property->id,
            'user_id' => $user->id,
            'status' => InquiryStatus::New,
        ]);

        $inquiry->markContacted();

        Notification::assertSentTo($user, InquiryStatusUpdatedNotification::class);
    });

    it('does not dispatch to user when status is unchanged', function () {
        Notification::fake();
        Mail::fake();

        $user = User::factory()->create();
        $store = Store::factory()->create();
        $property = Property::factory()->create(['store_id' => $store->id]);
        $inquiry = PropertyInquiry::factory()->create([
            'store_id' => $store->id,
            'property_id' => $property->id,
            'user_id' => $user->id,
            'status' => InquiryStatus::New,
        ]);

        $inquiry->update(['agent_notes' => 'Just a note']);

        Notification::assertNothingSentTo($user, InquiryStatusUpdatedNotification::class);
    });
});
