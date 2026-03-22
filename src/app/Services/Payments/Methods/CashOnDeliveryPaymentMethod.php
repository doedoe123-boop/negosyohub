<?php

namespace App\Services\Payments\Methods;

use App\Models\Store;
use App\OrderPaymentMethod;
use App\OrderPaymentStatus;
use App\Services\OrderService;
use App\Services\Payments\Contracts\CheckoutPaymentMethod;
use App\Services\Payments\PaymentResult;
use Illuminate\Validation\ValidationException;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;

class CashOnDeliveryPaymentMethod implements CheckoutPaymentMethod
{
    public function __construct(
        private OrderService $orderService
    ) {}

    public function identifier(): string
    {
        return OrderPaymentMethod::CashOnDelivery->value;
    }

    public function initiate(Cart $cart, Store $store): PaymentResult
    {
        $order = $this->orderService->createFromCart($cart, $store, [
            'payment_method' => OrderPaymentMethod::CashOnDelivery->value,
            'payment_status' => OrderPaymentStatus::Unpaid->value,
            'paid_at' => null,
            'payment_intent_id' => null,
            'payment_client_key' => null,
        ]);

        CartSession::manager()->clear();

        return PaymentResult::placed(
            OrderPaymentMethod::CashOnDelivery,
            $order,
            'Order placed successfully. Pay when your order arrives.'
        );
    }

    public function complete(array $payload): PaymentResult
    {
        throw ValidationException::withMessages([
            'payment_method' => 'Cash on Delivery does not require online payment completion.',
        ]);
    }
}
