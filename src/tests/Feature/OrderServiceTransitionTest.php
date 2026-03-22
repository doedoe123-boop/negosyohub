<?php

use App\Models\Order;
use App\Models\Store;
use App\OrderStatus;
use App\Services\OrderService;
use Illuminate\Validation\ValidationException;
use Lunar\Models\Currency;

describe('OrderService — Status Transitions', function () {

    beforeEach(function () {
        // Lunar's Price cast requires a default Currency in the DB
        Currency::factory()->create(['default' => true, 'code' => 'PHP']);

        $this->store = Store::factory()->create(['commission_rate' => 15.00]);
        $this->service = app(OrderService::class);
    });

    it('confirms a pending order', function () {
        $order = Order::factory()->for($this->store)->create([
            'status' => OrderStatus::Pending->value,
        ]);

        $updated = $this->service->confirm($order);

        expect($updated->status)->toBe(OrderStatus::Confirmed->value);
    });

    it('marks a confirmed order as preparing', function () {
        $order = Order::factory()->for($this->store)->create([
            'status' => OrderStatus::Confirmed->value,
        ]);

        $updated = $this->service->markPreparing($order);

        expect($updated->status)->toBe(OrderStatus::Preparing->value);
    });

    it('marks a preparing order as shipped', function () {
        $order = Order::factory()->for($this->store)->create([
            'status' => OrderStatus::Preparing->value,
        ]);

        $updated = $this->service->markShipped($order);

        expect($updated->status)->toBe(OrderStatus::Shipped->value);
    });

    it('marks a shipped order as delivered', function () {
        $order = Order::factory()->for($this->store)->create([
            'status' => OrderStatus::Shipped->value,
        ]);

        $updated = $this->service->markDelivered($order);

        expect($updated->status)->toBe(OrderStatus::Delivered->value);
    });

    it('cancels an active order', function () {
        $order = Order::factory()->for($this->store)->create([
            'status' => OrderStatus::Confirmed->value,
        ]);

        $updated = $this->service->cancel($order);

        expect($updated->status)->toBe(OrderStatus::Cancelled->value);
    });

    it('cannot confirm an already confirmed order', function () {
        $order = Order::factory()->for($this->store)->create([
            'status' => OrderStatus::Confirmed->value,
        ]);

        expect(fn () => $this->service->confirm($order))
            ->toThrow(ValidationException::class);
    });

    it('cannot cancel an already delivered order', function () {
        $order = Order::factory()->for($this->store)->delivered()->create();

        expect(fn () => $this->service->cancel($order))
            ->toThrow(ValidationException::class);
    });

    it('cannot cancel an already cancelled order', function () {
        $order = Order::factory()->for($this->store)->cancelled()->create();

        expect(fn () => $this->service->cancel($order))
            ->toThrow(ValidationException::class);
    });
});

describe('OrderService — Summarize', function () {

    it('summarizes an order correctly', function () {
        Currency::factory()->create(['default' => true, 'code' => 'PHP']);
        $store = Store::factory()->create(['name' => 'Test Store', 'commission_rate' => 15.00]);
        $order = Order::factory()->for($store)->create([
            'status' => OrderStatus::Pending->value,
        ]);

        $summary = app(OrderService::class)->summarize($order);

        expect($summary)
            ->toHaveKey('order_id', $order->id)
            ->toHaveKey('store', 'Test Store')
            ->toHaveKey('status', OrderStatus::Pending->value);
    });
});
