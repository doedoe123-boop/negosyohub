<?php

use App\InquiryStatus;
use App\Mail\InquiryAutoResponder;
use App\Mail\InquiryStatusUpdated;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\Store;
use App\Models\User;
use App\Notifications\NewInquiryNotification;
use App\UserRole;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

describe('New Inquiry — agent notifications', function () {

    it('sends auto-responder email and in-app notification to agent on new inquiry', function () {
        Notification::fake();
        Mail::fake();

        $agent = User::factory()->create(['role' => UserRole::StoreOwner]);
        $store = Store::factory()->create(['user_id' => $agent->id]);
        $property = Property::factory()->create(['store_id' => $store->id]);

        $inquiry = PropertyInquiry::factory()->create([
            'store_id' => $store->id,
            'property_id' => $property->id,
            'email' => 'buyer@example.com',
        ]);

        Mail::assertQueued(InquiryAutoResponder::class, fn ($mail) => $mail->hasTo('buyer@example.com'));
        Notification::assertSentTo($agent, NewInquiryNotification::class);
    });

    it('NewInquiryNotification only uses the database channel', function () {
        $inquiry = PropertyInquiry::factory()->create();
        $notification = new NewInquiryNotification($inquiry);

        expect($notification->via(new \stdClass))->toBe(['database']);
    });

    it('NewInquiryNotification body contains the inquirer name', function () {
        $store = Store::factory()->create();
        $property = Property::factory()->create([
            'store_id' => $store->id,
            'title' => 'Sunset Villa',
        ]);
        $inquiry = PropertyInquiry::factory()->create([
            'store_id' => $store->id,
            'property_id' => $property->id,
            'name' => 'Maria Santos',
        ]);

        $agent = User::factory()->create();
        $payload = (new NewInquiryNotification($inquiry))->toDatabase($agent);

        expect($payload)->toHaveKey('title')
            ->and($payload['body'])->toContain('Maria Santos');
    });

    it('NewInquiryNotification database payload has the expected title', function () {
        $inquiry = PropertyInquiry::factory()->create();
        $agent = User::factory()->create();
        $payload = (new NewInquiryNotification($inquiry))->toDatabase($agent);

        expect($payload['title'])->toBe('New Property Inquiry');
    });
});

describe('Inquiry Status Updates — inquirer email notifications', function () {

    it('sends status update email when inquiry status changes', function () {
        Mail::fake();

        $store = Store::factory()->create();
        $property = Property::factory()->create(['store_id' => $store->id]);
        $inquiry = PropertyInquiry::factory()->create([
            'store_id' => $store->id,
            'property_id' => $property->id,
            'status' => InquiryStatus::New,
            'email' => 'buyer@example.com',
        ]);

        $inquiry->markContacted();

        Mail::assertQueued(InquiryStatusUpdated::class, fn ($mail) => $mail->hasTo('buyer@example.com'));
    });

    it('sends status update email when viewing is scheduled', function () {
        Mail::fake();

        $store = Store::factory()->create();
        $property = Property::factory()->create(['store_id' => $store->id]);
        $inquiry = PropertyInquiry::factory()->contacted()->create([
            'store_id' => $store->id,
            'property_id' => $property->id,
            'email' => 'buyer@example.com',
        ]);

        $inquiry->scheduleViewing(now()->addDays(3));

        Mail::assertQueued(InquiryStatusUpdated::class, fn ($mail) => $mail->hasTo('buyer@example.com'));
    });

    it('does not send status update email when status is unchanged', function () {
        Mail::fake();

        $inquiry = PropertyInquiry::factory()->create(['status' => InquiryStatus::New]);

        // Update a non-status field
        $inquiry->update(['agent_notes' => 'Added a note']);

        Mail::assertNotQueued(InquiryStatusUpdated::class);
    });

    it('does not send status update email when inquiry has no email', function () {
        Mail::fake();

        $store = Store::factory()->create();
        $property = Property::factory()->create(['store_id' => $store->id]);
        $inquiry = PropertyInquiry::factory()->create([
            'store_id' => $store->id,
            'property_id' => $property->id,
            'status' => InquiryStatus::New,
            'email' => '',
        ]);

        $inquiry->update(['status' => InquiryStatus::Contacted]);

        Mail::assertNotQueued(InquiryStatusUpdated::class);
    });
});
