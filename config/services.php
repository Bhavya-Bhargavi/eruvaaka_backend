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

    'eruvaaka' => [
        'rss_url' => env('ERUVAAKA_RSS_URL', 'https://eruvaaka.com/feed/'),
    ],

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'plans' => [
            'yearly' => ['amount' => (int) env('RAZORPAY_YEARLY_AMOUNT', 9999) * 100, 'days' => 365],
        ],
    ],

    'msg91' => [
        'enabled' => (bool) env('MSG91_ENABLED', false),
        'authkey' => env('MSG91_AUTH_KEY'),
        'sender' => env('MSG91_SENDER_ID'),
        'template_id' => env('MSG91_TEMPLATE_ID'),
        'route' => env('MSG91_ROUTE', 4),
        'country' => env('MSG91_COUNTRY', 91),
        'ca_bundle' => env('MSG91_CA_BUNDLE'),
        'test_mode' => (bool) env('OTP_TEST_MODE', false),
        'test_phones' => array_values(array_filter(array_map('trim', explode(',', (string) env('OTP_TEST_PHONES', env('OTP_TEST_PHONE', '')))))),
    ],

];
