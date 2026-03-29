<?php

use App\Livewire\Store\StoreLogin;
use App\Models\Store;
use App\Models\User;
use App\Notifications\SellerEmailVerificationNotification;
use App\StoreStatus;
use App\UserRole;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'store_owner', 'guard_name' => 'web']);
});

it('verifies customer emails from the signed link without requiring an authenticated session', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->getEmailForVerification()),
    ]);

    $this->get($url)
        ->assertRedirect(rtrim(config('app.frontend_url'), '/').'/email/verified');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('redirects approved sellers to their store login URL after verification', function () {
    $owner = User::factory()->storeOwner()->unverified()->create([
        'role' => UserRole::StoreOwner,
    ]);
    $owner->assignRole('store_owner');

    $store = Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Approved,
        'login_token' => 'stk_verified_redirect_token',
        'sector' => 'ecommerce',
    ]);

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $owner->id,
        'hash' => sha1($owner->getEmailForVerification()),
    ]);

    $this->get($url)
        ->assertRedirect($store->loginUrl().'?verified=1');

    expect($owner->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('blocks unverified approved sellers from subdomain login and resends verification', function () {
    Notification::fake();

    $owner = User::factory()->storeOwner()->unverified()->create([
        'role' => UserRole::StoreOwner,
    ]);
    $owner->assignRole('store_owner');

    $store = Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Approved,
        'sector' => 'ecommerce',
    ]);

    app()->instance('currentStore', $store);

    Livewire::withoutLazyLoading()
        ->test(StoreLogin::class, ['token' => $store->login_token])
        ->set('email', $owner->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('email');

    Notification::assertSentTo($owner, SellerEmailVerificationNotification::class);
    $this->assertGuest();
});
