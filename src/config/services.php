<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'paymongo' => [
        'public_key' => env('PAYMONGO_PUBLIC_KEY'),
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'webhook_secret_test' => env('PAYMONGO_WEBHOOK_SECRET_TEST'),
        'webhook_secret_live' => env('PAYMONGO_WEBHOOK_SECRET_LIVE'),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PayPal
    |--------------------------------------------------------------------------
    | Set PAYPAL_ENV=sandbox for testing or PAYPAL_ENV=live for production.
    | Credentials are created at https://developer.paypal.com/dashboard/
    |
    */
    'paypal' => [
        'env' => env('PAYPAL_ENV', 'sandbox'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
    ],

    'turnstile' => [
        'enabled' => env('TURNSTILE_ENABLED', false),
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'newsletter_list_id' => env('BREVO_NEWSLETTER_LIST_ID'),
        'base_url' => env('BREVO_BASE_URL', 'https://api.brevo.com/v3'),
    ],

];
