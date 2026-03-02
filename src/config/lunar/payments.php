<?php

return [

    'default' => env('PAYMENTS_TYPE', 'paypal'),

    'types' => [
        'paypal' => [
            'driver' => 'paypal',
        ],

        // Keep as COD / offline fallback option
        'cash-in-hand' => [
            'driver' => 'offline',
            'authorized' => 'payment-offline',
        ],
    ],

    'paypal' => [
        // Named route PayPal redirects to after customer approves payment
        'success_route' => 'checkout.success',
    ],

];
