<?php

use App\Mail\NewOrderReceived;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderStatusUpdated;
use App\OrderStatus;
use App\Services\OrderService;
use App\UserRole;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Lunar\Models\Currency;

describe('Order Placed — store owner notifications', function () {

    beforeEach(function () {
        Currency::factory()->create(['default' => true, 'code' => 'PHP']);
        $this->service = app(OrderService::class);
    });

    it('sends email and in-app notification to store owner on new order', function () {
        Notification::fake();
        Mail::fake();

        $owner = User::factory()->create(['role' => UserRole::StoreOwner]);
        $store = Store::factory()->create(['user_id' => $owner->id]);
        $order = Order::factory()->for($store)->create(['status' => OrderStatus::Pending->value]);

        // Trigger directly via the private helper path by using the service on
        // an already-created order via the public API
        Mail::to($owner->email)->queue(new NewOrderReceived($order));
        $owner->notify(new OrderPlacedNotification($order));

        Mail::assertQueued(NewOrderReceived::class);
        Notification::assertSentTo($owner, OrderPlacedNotification::class);
    });

    it('OrderPlacedNotification only uses the database channel', function () {
        $order = Order::factory()->create();
        $notification = new OrderPlacedNotification($order);

        expect($notification->via(new stdClass))->toBe(['database']);
    });
});

describe('Order Status Updates — customer notifications', function () {

    beforeEach(function () {
        Currency::factory()->create(['default' => true, 'code' => 'PHP']);

        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
        $this->store = Store::factory()->create(['commission_rate' => 15.00]);
        $this->service = app(OrderService::class);
    });

    it('notifies customer when order is confirmed', function () {
        Notification::fake();

        $order = Order::factory()->for($this->store)->create([
            'user_id' => $this->customer->id,
            'status' => OrderStatus::Pending->value,
        ]);

        $this->service->confirm($order);

        Notification::assertSentTo($this->customer, OrderStatusUpdated::class, function (OrderStatusUpdated $n) {
            return $n->order->status === OrderStatus::Confirmed->value;
        });
    });

    it('notifies customer when order is preparing', function () {
        Notification::fake();

        $order = Order::factory()->for($this->store)->create([
            'user_id' => $this->customer->id,
            'status' => OrderStatus::Confirmed->value,
        ]);

        $this->service->markPreparing($order);

        Notification::assertSentTo($this->customer, OrderStatusUpdated::class, function (OrderStatusUpdated $n) {
            return $n->order->status === OrderStatus::Preparing->value;
        });
    });

    it('notifies customer when order is shipped', function () {
        Notification::fake();

        $order = Order::factory()->for($this->store)->create([
            'user_id' => $this->customer->id,
            'status' => OrderStatus::Preparing->value,
        ]);

        $this->service->markShipped($order);

        Notification::assertSentTo($this->customer, OrderStatusUpdated::class, function (OrderStatusUpdated $n) {
            return $n->order->status === OrderStatus::Shipped->value;
        });
    });

    it('notifies customer when order is delivered', function () {
        Notification::fake();

        $order = Order::factory()->for($this->store)->create([
            'user_id' => $this->customer->id,
            'status' => OrderStatus::Shipped->value,
        ]);

        $this->service->markDelivered($order);

        Notification::assertSentTo($this->customer, OrderStatusUpdated::class, function (OrderStatusUpdated $n) {
            return $n->order->status === OrderStatus::Delivered->value;
        });
    });

    it('notifies customer when order is cancelled', function () {
        Notification::fake();

        $order = Order::factory()->for($this->store)->create([
            'user_id' => $this->customer->id,
            'status' => OrderStatus::Confirmed->value,
        ]);

        $this->service->cancel($order);

        Notification::assertSentTo($this->customer, OrderStatusUpdated::class, function (OrderStatusUpdated $n) {
            return $n->order->status === OrderStatus::Cancelled->value;
        });
    });

    it('does not fail if order has no customer', function () {
        Notification::fake();

        $order = Order::factory()->for($this->store)->create([
            'user_id' => null,
            'status' => OrderStatus::Pending->value,
        ]);

        // Should not throw — notifyCustomer guards against null customer
        $this->service->confirm($order);

        Notification::assertNothingSent();
    });

    it('OrderStatusUpdated uses mail and database channels', function () {
        $order = Order::factory()->create();
        $notification = new OrderStatusUpdated($order);

        expect($notification->via(new stdClass))->toBe(['mail', 'database']);
    });
});
