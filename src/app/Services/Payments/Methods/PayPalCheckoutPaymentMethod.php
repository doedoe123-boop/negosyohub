<?php

namespace App\Services\Payments\Methods;

use App\Models\Store;
use App\OrderPaymentMethod;
use App\OrderPaymentStatus;
use App\Services\CheckoutDiscountService;
use App\Services\OrderService;
use App\Services\Payments\Contracts\CheckoutPaymentMethod;
use App\Services\Payments\PaymentResult;
use App\Services\PayPalService;
use Illuminate\Validation\ValidationException;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;

class PayPalCheckoutPaymentMethod implements CheckoutPaymentMethod
{
    public function __construct(
        private PayPalService $payPalService,
        private OrderService $orderService,
        private CheckoutDiscountService $checkoutDiscountService
    ) {}

    public function identifier(): string
    {
        return OrderPaymentMethod::PayPal->value;
    }

    public function initiate(Cart $cart, Store $store): PaymentResult
    {
        $discountSummary = $this->checkoutDiscountService->summarizeCart($cart, $store);
        $result = $this->payPalService->createOrder($cart, $discountSummary['total_after_discount']);

        if (! ($result['id'] ?? null) || ! ($result['approve_url'] ?? null)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Failed to create the PayPal checkout. Please try again.',
            ]);
        }

        return PaymentResult::redirect(
            OrderPaymentMethod::PayPal,
            $result['approve_url'],
            $result['id'],
            'Redirecting to PayPal.'
        );
    }

    public function complete(array $payload): PaymentResult
    {
        $paypalOrderId = (string) ($payload['paypal_order_id'] ?? '');
        $store = $payload['store'] ?? null;

        if (! $store instanceof Store) {
            throw ValidationException::withMessages([
                'store_id' => 'A valid store is required to complete PayPal checkout.',
            ]);
        }

        $paypalOrder = $this->payPalService->getOrder($paypalOrderId);
        $status = $paypalOrder['status'] ?? null;

        if (! in_array($status, ['APPROVED', 'COMPLETED'], true)) {
            throw ValidationException::withMessages([
                'paypal_order_id' => 'PayPal order has not been approved by the customer.',
            ]);
        }

        if ($status === 'APPROVED') {
            $paypalOrder = $this->payPalService->captureOrder($paypalOrderId);
        }

        if (($paypalOrder['status'] ?? null) !== 'COMPLETED') {
            throw ValidationException::withMessages([
                'paypal_order_id' => 'PayPal payment capture failed. Please try again.',
            ]);
        }

        $cart = CartSession::current();

        if (! $cart) {
            throw ValidationException::withMessages([
                'cart' => 'Cart session expired. Please try again.',
            ]);
        }

        $captureId = $paypalOrder['purchase_units'][0]['payments']['captures'][0]['id'] ?? $paypalOrderId;

        $order = $this->orderService->createFromCart($cart, $store, [
            'payment_method' => OrderPaymentMethod::PayPal->value,
            'payment_status' => OrderPaymentStatus::Paid->value,
            'payment_intent_id' => $paypalOrderId,
            'paid_at' => now(),
        ]);

        $this->orderService->recordPaymentCapture(
            $order,
            driver: OrderPaymentMethod::PayPal->value,
            reference: $captureId,
            status: 'COMPLETED',
        );

        CartSession::manager()->clear();

        return PaymentResult::placed(
            OrderPaymentMethod::PayPal,
            $order,
            'Payment captured and order placed successfully.'
        );
    }
}
