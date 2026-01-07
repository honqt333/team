<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway that will be used when
    | processing payments. You can switch this at runtime.
    |
    | Supported: "moyasar", "tap", "paytabs"
    |
    */
    'default' => env('PAYMENT_GATEWAY', 'moyasar'),

    /*
    |--------------------------------------------------------------------------
    | Payment Mode
    |--------------------------------------------------------------------------
    |
    | Set to 'test' for sandbox/testing, 'live' for production.
    |
    */
    'mode' => env('PAYMENT_MODE', 'test'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */
    'currency' => env('PAYMENT_CURRENCY', 'SAR'),

    /*
    |--------------------------------------------------------------------------
    | Callback URLs
    |--------------------------------------------------------------------------
    */
    'callback_url' => env('PAYMENT_CALLBACK_URL', '/payment/callback'),
    'success_url' => env('PAYMENT_SUCCESS_URL', '/payment/success'),
    'failed_url' => env('PAYMENT_FAILED_URL', '/payment/failed'),

    /*
    |--------------------------------------------------------------------------
    | Gateway Configurations
    |--------------------------------------------------------------------------
    */
    'gateways' => [
        'moyasar' => [
            'publishable_key' => env('MOYASAR_PUBLISHABLE_KEY'),
            'secret_key' => env('MOYASAR_SECRET_KEY'),
            'base_url' => env('MOYASAR_BASE_URL', 'https://api.moyasar.com/v1'),
        ],

        'tap' => [
            'public_key' => env('TAP_PUBLIC_KEY'),
            'secret_key' => env('TAP_SECRET_KEY'),
            'base_url' => env('TAP_BASE_URL', 'https://api.tap.company/v2'),
        ],

        'paytabs' => [
            'profile_id' => env('PAYTABS_PROFILE_ID'),
            'server_key' => env('PAYTABS_SERVER_KEY'),
            'client_key' => env('PAYTABS_CLIENT_KEY'),
            'base_url' => env('PAYTABS_BASE_URL', 'https://secure.paytabs.sa'),
        ],

        'tabby' => [
            'public_key' => env('TABBY_PUBLIC_KEY'),
            'secret_key' => env('TABBY_SECRET_KEY'),
            'merchant_code' => env('TABBY_MERCHANT_CODE'),
            'base_url' => env('TABBY_BASE_URL', 'https://api.tabby.ai/api/v2'),
        ],

        'tamara' => [
            'api_token' => env('TAMARA_API_TOKEN'),
            'notification_token' => env('TAMARA_NOTIFICATION_TOKEN'),
            'base_url' => env('TAMARA_BASE_URL', 'https://api.tamara.co'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Payment Methods
    |--------------------------------------------------------------------------
    */
    'payment_methods' => [
        'creditcard' => true,
        'mada' => true,
        'applepay' => true,
        'stcpay' => false, // Enable when supported
    ],
];
