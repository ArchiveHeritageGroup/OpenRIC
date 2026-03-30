<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cart Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the OpenRiC Cart package.
    | This package manages shopping carts and orders using the RiC-O data model.
    |
    */

    // Currency settings
    'currency' => env('CART_CURRENCY', 'ZAR'),
    'currency_symbol' => env('CART_CURRENCY_SYMBOL', 'R'),

    // Cart settings
    'cart_expiry_hours' => env('CART_EXPIRY_HOURS', 72),
    'max_items' => env('CART_MAX_ITEMS', 50),

    // Payment gateways
    'payment_gateway' => env('CART_PAYMENT_GATEWAY', 'manual'),
    'payment_gateways' => [
        'manual' => 'Manual/Invoice',
        'payfast' => 'PayFast',
        'stripe' => 'Stripe',
    ],

    // Order statuses
    'order_statuses' => [
        'pending' => 'Pending Payment',
        'paid' => 'Paid',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ],

    // Email notifications
    'notify_on_checkout' => env('CART_NOTIFY_CHECKOUT', true),
    'notify_on_payment' => env('CART_NOTIFY_PAYMENT', true),
    'notify_on_shipment' => env('CART_NOTIFY_SHIPMENT', true),

    // Digital vs Physical items
    'digital_delivery_email' => env('CART_DIGITAL_EMAIL', true),
];
