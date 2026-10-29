<?php

$yearlyAmount = (int) env('RAZORPAY_YEARLY_AMOUNT', 750);

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

    'eruvaaka_mart' => [
        'products_url' => env('ERUVAAKA_MART_PRODUCTS_URL', 'https://eruvaakamart.com/wp-json/wc/store/v1/products'),
    ],

    'eruvaaka' => [
        'rss_url' => env('ERUVAAKA_RSS_URL', 'https://eruvaaka.com/feed/'),
        'wp_api_posts_url' => env('ERUVAAKA_WP_API_POSTS_URL', 'https://eruvaaka.com/wp-json/wp/v2/posts'),
        'wp_api_media_url' => env('ERUVAAKA_WP_API_MEDIA_URL', 'https://eruvaaka.com/wp-json/wp/v2/media'),
    ],

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'test_mode' => (bool) env('RAZORPAY_TEST_MODE', false),
        'test_amount' => (int) env('RAZORPAY_TEST_AMOUNT', 2) * 100,
        'plans' => [
            '1year' => ['name' => '1 Year', 'type' => 'legacy', 'amount' => (int) env('RAZORPAY_1YEAR_AMOUNT', $yearlyAmount) * 100, 'years' => 1, 'products' => ['emagazine', 'print_magazine']],
            '3year' => ['name' => '3 Years', 'type' => 'legacy', 'amount' => (int) env('RAZORPAY_3YEAR_AMOUNT', 20) * 100, 'years' => 3, 'products' => ['emagazine', 'print_magazine']],
            '5year' => ['name' => '5 Years', 'type' => 'legacy', 'amount' => (int) env('RAZORPAY_5YEAR_AMOUNT', 30) * 100, 'years' => 5, 'products' => ['emagazine', 'print_magazine']],
            'emagazine_monthly' => ['name' => 'E-Magazine Monthly', 'type' => 'emagazine', 'amount' => (int) env('RAZORPAY_EMAGAZINE_MONTHLY_AMOUNT', 99) * 100, 'months' => 1, 'products' => ['emagazine']],
            'print_magazine_monthly' => ['name' => 'Print Magazine Monthly', 'type' => 'print_magazine', 'amount' => (int) env('RAZORPAY_PRINT_MAGAZINE_MONTHLY_AMOUNT', 299) * 100, 'months' => 1, 'products' => ['print_magazine']],
            'combo_monthly' => ['name' => 'E + Print Combo', 'type' => 'combo', 'amount' => (int) env('RAZORPAY_COMBO_MONTHLY_AMOUNT', 349) * 100, 'months' => 1, 'products' => ['emagazine', 'print_magazine']],
        ],
        'entitlement_plans' => [
            'emagazine' => 'emagazine_monthly',
            'print_magazine' => 'print_magazine_monthly',
        ],
    ],

    'otp' => [
        'expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 5),
        'test_mode' => (bool) env('OTP_TEST_MODE', false),
        'test_phone' => env('OTP_TEST_PHONE'),
    ],
    'infinito_sms' => [
        'enabled' => (bool) env('INFINITO_SMS_ENABLED', false),
        'endpoint' => env('INFINITO_API_URL', 'https://api.goinfinito.com/unified/v2/send'),
        'api_token' => env('INFINITO_API_TOKEN'),
        'from' => env('INFINITO_SMS_FROM'),
        'otp_dlt_template_id' => env('INFINITO_SMS_OTP_DLT_TEMPLATE_ID'),
        'otp_template' => env('INFINITO_SMS_OTP_TEMPLATE'),
        'payment_dlt_template_id' => env('INFINITO_SMS_PAYMENT_DLT_TEMPLATE_ID'),
        'payment_template' => env('INFINITO_SMS_PAYMENT_TEMPLATE'),
        'dlt_content_type' => env('INFINITO_SMS_DLT_CONTENT_TYPE', 1),
        'property' => env('INFINITO_SMS_PROPERTY', 0),
        'country_prefix' => env('INFINITO_SMS_COUNTRY_PREFIX', '91'),
        'timeout' => env('INFINITO_SMS_TIMEOUT', 10),
        'ca_bundle' => env('INFINITO_CA_BUNDLE'),
        'test_mode' => (bool) env('INFINITO_SMS_TEST_MODE', false),
        'test_phones' => array_values(array_filter(array_map('trim', explode(',', (string) env('INFINITO_SMS_TEST_PHONES', ''))))),
    ],

];
