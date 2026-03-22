<?php

use Lunar\Base\OrderReferenceGenerator;
use Lunar\Pipelines\Order\Creation\CleanUpOrderLines;
use Lunar\Pipelines\Order\Creation\CreateOrderAddresses;
use Lunar\Pipelines\Order\Creation\CreateOrderLines;
use Lunar\Pipelines\Order\Creation\CreateShippingLine;
use Lunar\Pipelines\Order\Creation\FillOrderFromCart;
use Lunar\Pipelines\Order\Creation\MapDiscountBreakdown;

return [
    /*
    |--------------------------------------------------------------------------
    | Order Reference Format
    |--------------------------------------------------------------------------
    |
    | Specify the format for the order reference generator to use.
    |
    */
    'reference_format' => [
        /**
         * Optional prefix for the order reference
         */
        'prefix' => null,

        /**
         * STR_PAD_LEFT: 00001965
         * STR_PAD_RIGHT: 19650000
         * STR_PAD_BOTH: 00196500
         */
        'padding_direction' => STR_PAD_LEFT,

        /**
         * 00001965
         * AAAA1965
         */
        'padding_character' => '0',

        /**
         * If the length specified below is smaller than the length
         * of the Order ID, then no padding will take place.
         */
        'length' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Reference Generator
    |--------------------------------------------------------------------------
    |
    | Here you can specify how you want your order references to be generated
    | when you create an order from a cart.
    |
    */
    'reference_generator' => OrderReferenceGenerator::class,

    /*
    |--------------------------------------------------------------------------
    | Draft Status
    |--------------------------------------------------------------------------
    |
    | When a draft order is created from a cart, we need an initial status for
    | the order that's created. Define that here, it can be anything that would
    | make sense for the store you're building.
    |
    */
    'draft_status' => 'pending',

    'statuses' => [

        'pending' => [
            'label' => 'Pending',
            'color' => '#F59E0B',
            'favourite' => true,
        ],

        'confirmed' => [
            'label' => 'Confirmed',
            'color' => '#0EA5E9',
            'favourite' => true,
        ],

        'preparing' => [
            'label' => 'Preparing',
            'color' => '#8B5CF6',
            'favourite' => true,
        ],

        'shipped' => [
            'label' => 'Shipped',
            'color' => '#10B981',
            'favourite' => true,
        ],

        'delivered' => [
            'label' => 'Delivered',
            'color' => '#22C55E',
            'favourite' => true,
        ],

        'cancelled' => [
            'label' => 'Cancelled',
            'color' => '#EF4444',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Order Pipelines
    |--------------------------------------------------------------------------
    |
    | Define which pipelines should be run throughout an order's lifecycle.
    | The default ones provided should suit most needs, however you are
    | free to add your own as you see fit.
    |
    | Each pipeline class will be run from top to bottom.
    |
    */
    'pipelines' => [
        'creation' => [
            FillOrderFromCart::class,
            CreateOrderLines::class,
            CreateOrderAddresses::class,
            CreateShippingLine::class,
            CleanUpOrderLines::class,
            MapDiscountBreakdown::class,
        ],
    ],

];
