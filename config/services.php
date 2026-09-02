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

    'cmi' => [
        'client_id' => env('CMI_CLIENT_ID'),
        'store_key' => env('CMI_STORE_KEY'),
        'gateway_url' => env('CMI_GATEWAY_URL', 'https://testpayment.cmi.co.ma/fim/est3Dgate'),
        'ok_url' => env('CMI_OK_URL', env('APP_URL').'/api/payments/cmi/ok'),
        'fail_url' => env('CMI_FAIL_URL', env('APP_URL').'/api/payments/cmi/fail'),
        'callback_url' => env('CMI_CALLBACK_URL', env('APP_URL').'/api/payments/cmi/callback'),
        'currency' => env('CMI_CURRENCY', '504'),
        'store_type' => env('CMI_STORE_TYPE', '3D_PAY_HOSTING'),
        'transaction_type' => env('CMI_TRANSACTION_TYPE', 'Auth'),
        'language' => env('CMI_LANGUAGE', 'fr'),
        'test_mode' => env('CMI_TEST_MODE', true),
    ],

];
