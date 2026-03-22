<?php

namespace App\Services\Payments\Contracts;

use App\Models\Store;
use App\Services\Payments\PaymentResult;
use Lunar\Models\Cart;

interface CheckoutPaymentMethod
{
    public function identifier(): string;

    public function initiate(Cart $cart, Store $store): PaymentResult;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function complete(array $payload): PaymentResult;
}
