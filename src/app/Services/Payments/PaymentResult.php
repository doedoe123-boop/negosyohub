<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\OrderPaymentMethod;

class PaymentResult
{
    public function __construct(
        public string $type,
        public OrderPaymentMethod $method,
        public ?Order $order = null,
        public ?string $message = null,
        public ?string $redirectUrl = null,
        public ?string $externalReference = null,
    ) {}

    public static function placed(OrderPaymentMethod $method, Order $order, string $message): self
    {
        return new self(
            type: 'placed',
            method: $method,
            order: $order,
            message: $message,
        );
    }

    public static function redirect(
        OrderPaymentMethod $method,
        string $redirectUrl,
        ?string $externalReference = null,
        ?string $message = null
    ): self {
        return new self(
            type: 'redirect',
            method: $method,
            message: $message,
            redirectUrl: $redirectUrl,
            externalReference: $externalReference,
        );
    }

    public function requiresRedirect(): bool
    {
        return $this->type === 'redirect';
    }
}
