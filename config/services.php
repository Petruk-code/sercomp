<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'fonnte' => [
        'api_key' => env('FONNTE_API_KEY'),
        'sender' => env('FONNTE_SENDER'),
        'country_code' => env('FONNTE_COUNTRY_CODE', '62'),
        'otp_expiry_minutes' => env('FONNTE_OTP_EXPIRY_MINUTES', 5),
        'otp_attempts' => env('FONNTE_OTP_ATTEMPTS', 3),
        'otp_force_send' => env('FONNTE_OTP_FORCE_SEND', false),
    ],

];
