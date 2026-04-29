<?php

use App\Models\Order;
use App\Models\User;
use App\Services\MarketplaceCartService;
use App\Services\MultiStoreCheckoutService;
use Illuminate\Support\Collection;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;
use Lunar\Models\Currency;

beforeEach(function () {
    Currency::query()->firstOrCreate(
        ['code' => 'PHP'],
        Currency::factory()->make(['default' => true, 'code' => 'PHP'])->toArray(),
    );
});

it('returns grouped order ids when placing a multi-store cash on delivery checkout', function () {
    $user = User::factory()->create();
    $cart = Cart::factory()->make(['customer_id' => $user->id]);
    $orders = Collection::make([
        Order::factory()->make(['id' => 101]),
        Order::factory()->make(['id' => 102]),
    ]);

    CartSession::shouldReceive('current')->twice()->andReturn($cart);
    $cartManager = Mockery::mock(Cart::class)->makePartial();
    $cartManager->shouldReceive('clear')->once()->andReturnSelf();
    CartSession::shouldReceive('manager')->once()->andReturn($cartManager);

    $this->mock(MarketplaceCartService::class)
        ->shouldReceive('hasMultipleStores')
        ->once()
        ->with($cart)
        ->andReturn(true);

    $this->mock(MultiStoreCheckoutService::class)
        ->shouldReceive('placeCashOnDelivery')
        ->once()
        ->with($cart)
        ->andReturn([
            'checkout_group_id' => 'group-123',
            'orders' => $orders,
            'order_ids' => [101, 102],
            'first_order_id' => 101,
            'applied_coupon' => null,
        ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'payment_method' => 'cash_on_delivery',
        ])
        ->assertCreated()
        ->assertJsonPath('order_id', 101)
        ->assertJsonPath('order_ids.0', 101)
        ->assertJsonPath('order_ids.1', 102)
        ->assertJsonPath('checkout_group_id', 'group-123');
});
