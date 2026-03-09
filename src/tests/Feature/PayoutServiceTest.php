<?php

use App\Models\Order;
use App\Models\Payout;
use App\Models\PayoutLine;
use App\Models\Store;
use App\OrderStatus;
use App\Services\PayoutService;
use Illuminate\Support\Carbon;

describe('PayoutService — generateForStore', function () {

    beforeEach(function () {
        \Lunar\Models\Currency::factory()->create(['default' => true, 'code' => 'PHP']);

        $this->store = Store::factory()->create(['commission_rate' => 15.00]);
        $this->service = app(PayoutService::class);
        $this->periodStart = Carbon::parse('2026-02-01');
        $this->periodEnd = Carbon::parse('2026-02-28');
    });

    it('generates a payout from delivered orders', function () {
        Order::factory()->for($this->store)->delivered()->count(3)->create([
            'placed_at' => Carbon::parse('2026-02-15'),
        ]);

        $payout = $this->service->generateForStore($this->store, $this->periodStart, $this->periodEnd);

        expect($payout)->toBeInstanceOf(Payout::class)
            ->and($payout->store_id)->toBe($this->store->id)
            ->and($payout->status)->toBe(Payout::STATUS_PENDING)
            ->and((float) $payout->amount)->toBeGreaterThan(0)
            ->and($payout->period_start->toDateString())->toBe('2026-02-01')
            ->and($payout->period_end->toDateString())->toBe('2026-02-28');

        expect(PayoutLine::where('payout_id', $payout->id)->count())->toBe(3);
    });

    it('returns null when no delivered orders exist', function () {
        Order::factory()->for($this->store)->create([
            'status' => OrderStatus::Pending->value,
            'placed_at' => Carbon::parse('2026-02-15'),
        ]);

        $payout = $this->service->generateForStore($this->store, $this->periodStart, $this->periodEnd);

        expect($payout)->toBeNull();
    });

    it('ignores orders outside the period', function () {
        Order::factory()->for($this->store)->delivered()->create([
            'placed_at' => Carbon::parse('2026-01-15'),
        ]);

        $payout = $this->service->generateForStore($this->store, $this->periodStart, $this->periodEnd);

        expect($payout)->toBeNull();
    });

    it('does not double-include orders already in a payout', function () {
        Order::factory()->for($this->store)->delivered()->create([
            'placed_at' => Carbon::parse('2026-02-15'),
        ]);

        // First payout includes the order
        $first = $this->service->generateForStore($this->store, $this->periodStart, $this->periodEnd);
        expect($first)->not->toBeNull();

        // Second attempt should return null — order already linked
        $second = $this->service->generateForStore($this->store, $this->periodStart, $this->periodEnd);
        expect($second)->toBeNull();
    });

    it('calculates payout amount from store_earning', function () {
        $order = Order::factory()->for($this->store)->delivered()->create([
            'store_earning' => 85000, // 850.00 PHP in cents
            'placed_at' => Carbon::parse('2026-02-15'),
        ]);

        $payout = $this->service->generateForStore($this->store, $this->periodStart, $this->periodEnd);

        expect((float) $payout->amount)->toBe(850.00);

        $line = PayoutLine::where('payout_id', $payout->id)->first();
        expect($line->store_earning)->toBe(85000)
            ->and($line->order_id)->toBe($order->id);
    });

    it('sums multiple orders into a single payout', function () {
        Order::factory()->for($this->store)->delivered()->create([
            'store_earning' => 50000,
            'placed_at' => Carbon::parse('2026-02-10'),
        ]);
        Order::factory()->for($this->store)->delivered()->create([
            'store_earning' => 30000,
            'placed_at' => Carbon::parse('2026-02-20'),
        ]);

        $payout = $this->service->generateForStore($this->store, $this->periodStart, $this->periodEnd);

        expect((float) $payout->amount)->toBe(800.00);
        expect(PayoutLine::where('payout_id', $payout->id)->count())->toBe(2);
    });
});

describe('PayoutService — generateAll', function () {

    beforeEach(function () {
        \Lunar\Models\Currency::factory()->create(['default' => true, 'code' => 'PHP']);
        $this->service = app(PayoutService::class);
    });

    it('generates payouts for all stores with delivered orders', function () {
        $store1 = Store::factory()->create();
        $store2 = Store::factory()->create();
        $store3 = Store::factory()->create();

        $period = Carbon::parse('2026-02-15');

        Order::factory()->for($store1)->delivered()->create(['placed_at' => $period]);
        Order::factory()->for($store2)->delivered()->create(['placed_at' => $period]);
        // store3 has no delivered orders

        $result = $this->service->generateAll(
            Carbon::parse('2026-02-01'),
            Carbon::parse('2026-02-28')
        );

        expect($result['generated'])->toBe(2)
            ->and($result['skipped'])->toBe(1);
        expect(Payout::count())->toBe(2);
    });
});

describe('PayoutService — generateWeekly', function () {

    beforeEach(function () {
        \Lunar\Models\Currency::factory()->create(['default' => true, 'code' => 'PHP']);
        $this->service = app(PayoutService::class);
    });

    it('generates payouts for the previous week', function () {
        $store = Store::factory()->create();
        $lastTuesday = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->addDay();

        Order::factory()->for($store)->delivered()->create([
            'placed_at' => $lastTuesday,
        ]);

        $result = $this->service->generateWeekly();

        expect($result['generated'])->toBe(1);

        $payout = Payout::first();
        expect($payout)->not->toBeNull()
            ->and($payout->period_start->isDayOfWeek(Carbon::MONDAY))->toBeTrue()
            ->and($payout->period_end->isDayOfWeek(Carbon::SUNDAY))->toBeTrue();
    });
});
