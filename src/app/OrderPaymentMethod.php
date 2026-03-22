<?php

namespace App;

enum OrderPaymentMethod: string
{
    case PayMongo = 'paymongo';
    case PayPal = 'paypal';
    case CashOnDelivery = 'cash_on_delivery';

    public function label(): string
    {
        return match ($this) {
            self::PayMongo => 'PayMongo',
            self::PayPal => 'PayPal',
            self::CashOnDelivery => 'Cash on Delivery',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PayMongo => 'Pay securely online with PayMongo.',
            self::PayPal => 'Pay securely online with PayPal.',
            self::CashOnDelivery => 'Pay when your order arrives.',
        };
    }
}
