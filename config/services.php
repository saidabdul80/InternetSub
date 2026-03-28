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
    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'default_email' => env('PAYSTACK_DEFAULT_EMAIL', 'captive@example.com'),
        'channels' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('PAYSTACK_CHANNELS', 'card,bank,ussd,bank_transfer,qr,mobile_money,eft'))
        ))),
    ],
    'monnify' => [
        'base_url' => env('MONNIFY_BASE_URL', 'https://sandbox.monnify.com'),
        'public_key' => env('MONNIFY_PUBLIC_KEY'),
        'secret_key' => env('MONNIFY_SECRET_KEY'),
        'contract_code' => env('MONNIFY_CONTRACT_CODE', ''),
    ],
    'sms' => [
        'base_url' => env('SMS_BASE_URL', 'https://v3.api.termii.com/api/sms/send'),
        'api_key' => env('SMS_API_KEY', 'XXX'),
        'email_address' => env('SMS_EMAIL_ADDRESS', 'XXX'),
        'sender_name' => 'N-Alert',
    ],
    'mikrotik' => [
        'host' => env('MIKROTIK_HOST', ''),
        'port' => env('MIKROTIK_PORT', 8728),
        'username' => env('MIKROTIK_USERNAME', ''),
        'password' => env('MIKROTIK_PASSWORD', ''),
        'use_ssl' => env('MIKROTIK_USE_SSL', false),
        'timeout' => env('MIKROTIK_TIMEOUT', 5),
        'provisioner' => env('MIKROTIK_PROVISIONER', 'hotspot'),
        'userman_customer' => env('MIKROTIK_USERMAN_CUSTOMER', 'admin'),
        'userman_shared_users' => env('MIKROTIK_USERMAN_SHARED_USERS', '1'),
        'default_profile' => env('MIKROTIK_DEFAULT_PROFILE', 'default'),
        'profile_map' => [
            1 => env('MIKROTIK_PROFILE_PLAN_1', '5G_profile_1d'),
            2 => env('MIKROTIK_PROFILE_PLAN_2', '10G_profile_3D'),
            3 => env('MIKROTIK_PROFILE_PLAN_3', '20G_profile_1W'),
            4 => env('MIKROTIK_PROFILE_PLAN_4', 'monthly'),
        ],
        'hotspot_plan_limits' => [
            1 => [
                'limit_uptime' => env('MIKROTIK_HOTSPOT_PLAN_1_UPTIME', '1d'),
                'limit_bytes_total' => env('MIKROTIK_HOTSPOT_PLAN_1_BYTES_TOTAL', '5368709120'),
            ],
            2 => [
                'limit_uptime' => env('MIKROTIK_HOTSPOT_PLAN_2_UPTIME', '3d'),
                'limit_bytes_total' => env('MIKROTIK_HOTSPOT_PLAN_2_BYTES_TOTAL', '10737418240'),
            ],
            3 => [
                'limit_uptime' => env('MIKROTIK_HOTSPOT_PLAN_3_UPTIME', '1w'),
                'limit_bytes_total' => env('MIKROTIK_HOTSPOT_PLAN_3_BYTES_TOTAL', '21474836480'),
            ],
            4 => [
                'limit_uptime' => env('MIKROTIK_HOTSPOT_PLAN_4_UPTIME', '30d'),
                'limit_bytes_total' => env('MIKROTIK_HOTSPOT_PLAN_4_BYTES_TOTAL', ''),
            ],
        ],
        'plan_duration_hours' => [
            1 => (int) env('MIKROTIK_PLAN_1_HOURS', 24),
            2 => (int) env('MIKROTIK_PLAN_2_HOURS', 72),
            3 => (int) env('MIKROTIK_PLAN_3_HOURS', 168),
            4 => (int) env('MIKROTIK_PLAN_4_HOURS', 720),
        ],
    ],
];
