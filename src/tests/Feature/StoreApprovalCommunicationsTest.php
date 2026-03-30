<?php

use App\Mail\StoreApproved;
use App\Models\Store;
use App\Models\User;
use App\Notifications\SellerEmailVerificationNotification;
use App\Services\StoreService;
use App\StoreStatus;
use App\UserRole;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'store_owner', 'guard_name' => 'web']);
});

it('sends approval and verification emails for approved unverified sellers', function () {
    Mail::fake();
    Notification::fake();

    $owner = User::factory()->storeOwner()->unverified()->create([
        'role' => UserRole::StoreOwner,
    ]);
    $owner->assignRole('store_owner');

    $store = Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Approved,
        'login_token' => null,
    ]);

    app(StoreService::class)->sendApprovalCommunications($store);

    Mail::assertSent(StoreApproved::class, function (StoreApproved $mail) use ($store): bool {
        return $mail->store->is($store->fresh());
    });

    Notification::assertSentTo($owner, SellerEmailVerificationNotification::class);

    expect($store->fresh()->login_token)->not->toBeNull();
});

it('resends only the approval email for approved verified sellers', function () {
    Mail::fake();
    Notification::fake();

    $owner = User::factory()->storeOwner()->create([
        'role' => UserRole::StoreOwner,
        'email_verified_at' => now(),
    ]);
    $owner->assignRole('store_owner');

    $store = Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Approved,
    ]);

    app(StoreService::class)->sendApprovalCommunications($store);

    Mail::assertSent(StoreApproved::class);
    Notification::assertNothingSent();
});

it('does nothing for non-approved stores', function () {
    Mail::fake();
    Notification::fake();

    $owner = User::factory()->storeOwner()->unverified()->create([
        'role' => UserRole::StoreOwner,
    ]);
    $owner->assignRole('store_owner');

    $store = Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Pending,
        'login_token' => null,
    ]);

    app(StoreService::class)->sendApprovalCommunications($store);

    Mail::assertNothingSent();
    Notification::assertNothingSent();

    expect($store->fresh()->login_token)->toBeNull();
});
