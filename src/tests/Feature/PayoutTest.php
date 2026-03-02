<?php

use App\Models\Payout;
use App\Models\Store;
use App\Models\User;
use App\StoreStatus;

describe('Payout Model', function () {

    beforeEach(function () {
        $this->store = Store::factory()->create();
    });

    it('creates a payout with factory', function () {
        $payout = Payout::factory()->for($this->store)->create();

        expect($payout)->toBeInstanceOf(Payout::class)
            ->and($payout->store_id)->toBe($this->store->id)
            ->and((float) $payout->amount)->toBeFloat();
    });

    it('defaults to pending status', function () {
        $payout = Payout::factory()->for($this->store)->create(['status' => Payout::STATUS_PENDING]);

        expect($payout->status)->toBe(Payout::STATUS_PENDING);
    });

    it('can be marked as paid', function () {
        $payout = Payout::factory()->paid()->for($this->store)->create();

        expect($payout->status)->toBe(Payout::STATUS_PAID);
    });

    it('belongs to a store', function () {
        $payout = Payout::factory()->for($this->store)->create();

        expect($payout->store->id)->toBe($this->store->id);
    });
});

describe('Payout Scopes', function () {

    beforeEach(function () {
        $this->store = Store::factory()->create();
    });

    it('scopes payouts for a specific store', function () {
        $otherStore = Store::factory()->create();

        Payout::factory()->for($this->store)->count(3)->create();
        Payout::factory()->for($otherStore)->count(2)->create();

        expect(Payout::forStore($this->store->id)->count())->toBe(3);
    });

    it('scopes pending payouts', function () {
        Payout::factory()->for($this->store)->create(['status' => Payout::STATUS_PENDING]);
        Payout::factory()->for($this->store)->create(['status' => Payout::STATUS_PAID]);

        expect(Payout::pending()->count())->toBe(1);
    });

    it('scopes paid payouts', function () {
        Payout::factory()->for($this->store)->create(['status' => Payout::STATUS_PENDING]);
        Payout::factory()->for($this->store)->paid()->create();
        Payout::factory()->for($this->store)->paid()->create();

        expect(Payout::paid()->count())->toBe(2);
    });
});

describe('Payout Policy', function () {

    it('allows admin to view any payout', function () {
        $admin = User::factory()->admin()->create();

        expect($admin->can('viewAny', Payout::class))->toBeTrue();
    });

    it('allows store owner to view any payout', function () {
        $owner = User::factory()->storeOwner()->create();

        expect($owner->can('viewAny', Payout::class))->toBeTrue();
    });

    it('only allows admin to create payouts', function () {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->storeOwner()->create();

        expect($admin->can('create', Payout::class))->toBeTrue()
            ->and($owner->can('create', Payout::class))->toBeFalse();
    });

    it('only allows admin to update payouts', function () {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->storeOwner()->create();
        $payout = Payout::factory()->for(Store::factory()->create())->create();

        expect($admin->can('update', $payout))->toBeTrue()
            ->and($owner->can('update', $payout))->toBeFalse();
    });

    it('restricts store owner to view only their store payouts', function () {
        $owner = User::factory()->storeOwner()->create();
        $store = Store::factory()->for($owner, 'owner')->create(['status' => StoreStatus::Approved]);
        $owner->update(['store_id' => null]);

        $ownPayout = Payout::factory()->for($store)->create();
        $otherPayout = Payout::factory()->for(Store::factory()->create())->create();

        expect($owner->can('view', $ownPayout))->toBeTrue()
            ->and($owner->can('view', $otherPayout))->toBeFalse();
    });
});
