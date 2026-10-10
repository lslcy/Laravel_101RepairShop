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

    // Used by /api/customer/* to verify Supabase Auth access tokens sent by the Flutter app.
    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'anon_key' => env('SUPABASE_ANON_KEY'),
        // Server-only access to private payment receipts and merchant QR uploads.
        // Support both the standard Supabase name and the shorter alias many setups use.
        'service_key' => env('SUPABASE_SERVICE_ROLE_KEY', env('SUPABASE_SERVICE_KEY')),
        'storage_timeout' => env('SUPABASE_STORAGE_TIMEOUT', 15),
    ],

    'paymongo' => [
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'minimum_amount' => env('PAYMONGO_MINIMUM_AMOUNT', 100),
        'timeout' => env('PAYMONGO_TIMEOUT', 10),
    ],

];
