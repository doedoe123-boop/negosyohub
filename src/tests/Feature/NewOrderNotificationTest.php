<?php

use App\Mail\NewOrderReceived;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\Mail;

describe('NewOrderReceived mailable', function () {

    it('queues notification to store owner when order is created', function () {
        Mail::fake();

        $owner = User::factory()->create(['role' => UserRole::StoreOwner]);
        $store = Store::factory()->create(['user_id' => $owner->id]);
        $order = Order::factory()->create(['store_id' => $store->id]);

        Mail::to($owner->email)->queue(new NewOrderReceived($order));

        Mail::assertQueued(NewOrderReceived::class, function (NewOrderReceived $mail) use ($owner, $order) {
            return $mail->order->id === $order->id
                && $mail->hasTo($owner->email);
        });
    });

    it('has the correct subject containing the order reference', function () {
        $order = Order::factory()->create();
        $mailable = new NewOrderReceived($order);

        expect($mailable->envelope()->subject)
            ->toBe('New Order Received — #'.$order->reference);
    });

    it('does not fail if the store has no owner email', function () {
        $order = Order::factory()->create(['store_id' => null]);

        // Should construct without throwing
        $mailable = new NewOrderReceived($order);

        expect($mailable->envelope()->subject)->toContain('#');
    });
});
