<?php

use App\Models\LoginHistory;
use App\Models\Store;
use App\Models\User;

describe('User Model — Disable/Enable', function () {

    it('can be soft-deleted (disabled)', function () {
        $user = User::factory()->create();

        $user->delete();

        expect(User::withTrashed()->find($user->id)->trashed())->toBeTrue();
    });

    it('can be restored (re-enabled) after being soft-deleted', function () {
        $user = User::factory()->create();
        $user->delete();

        $user->restore();

        expect(User::find($user->id))->not->toBeNull()
            ->and($user->fresh()->trashed())->toBeFalse();
    });

    it('is excluded from default queries when soft-deleted', function () {
        User::factory()->count(3)->create();
        $disabledUser = User::factory()->create();
        $disabledUser->delete();

        $count = User::count();

        expect($count)->toBe(3);
    });
});

describe('User Model — Relationships', function () {

    it('has many login history entries', function () {
        $user = User::factory()->create();
        LoginHistory::factory()->for($user)->count(3)->create();

        expect($user->loginHistory)->toHaveCount(3);
    });

    it('has many support tickets', function () {
        $user = User::factory()->create();

        expect($user->supportTickets())->not->toBeNull();
    });

    it('returns owned store', function () {
        $owner = User::factory()->storeOwner()->create();
        $store = Store::factory()->for($owner, 'owner')->create();

        expect($owner->store->id)->toBe($store->id);
    });
});

describe('User Policy', function () {

    it('allows admin to view any user', function () {
        $admin = User::factory()->admin()->create();

        expect($admin->can('viewAny', User::class))->toBeTrue();
    });

    it('allows admin to update any user', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        expect($admin->can('update', $user))->toBeTrue();
    });
});

describe('Store Model — scopeApproved', function () {

    it('returns only approved stores', function () {
        Store::factory()->create(['status' => \App\StoreStatus::Approved]);
        Store::factory()->create(['status' => \App\StoreStatus::Approved]);
        Store::factory()->create(['status' => \App\StoreStatus::Pending]);
        Store::factory()->create(['status' => \App\StoreStatus::Suspended]);

        expect(Store::approved()->count())->toBe(2);
    });
});
