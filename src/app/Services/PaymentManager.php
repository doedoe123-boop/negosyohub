<?php

namespace App\Services;

use App\Models\Store;
use App\OrderPaymentMethod;
use App\Services\Payments\Contracts\CheckoutPaymentMethod;
use App\Services\Payments\Methods\CashOnDeliveryPaymentMethod;
use App\Services\Payments\Methods\PayPalCheckoutPaymentMethod;
use App\Services\Payments\PaymentResult;
use Illuminate\Validation\ValidationException;
use Lunar\Models\Cart;

class PaymentManager
{
    /**
     * @var array<string, CheckoutPaymentMethod>
     */
    private array $methods;

    public function __construct(
        CashOnDeliveryPaymentMethod $cashOnDelivery,
        PayPalCheckoutPaymentMethod $payPal
    ) {
        $this->methods = [
            $cashOnDelivery->identifier() => $cashOnDelivery,
            $payPal->identifier() => $payPal,
        ];
    }

    public function initiate(string $method, Cart $cart, Store $store): PaymentResult
    {
        return $this->resolve($method)->initiate($cart, $store);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function complete(string $method, array $payload): PaymentResult
    {
        return $this->resolve($method)->complete($payload);
    }

    private function resolve(string $method): CheckoutPaymentMethod
    {
        $identifier = OrderPaymentMethod::tryFrom($method)?->value ?? $method;

        if (! array_key_exists($identifier, $this->methods)) {
            throw ValidationException::withMessages([
                'payment_method' => 'The selected payment method is not supported.',
            ]);
        }

        return $this->methods[$identifier];
    }
}
