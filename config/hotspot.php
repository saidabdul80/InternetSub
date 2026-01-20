<?php

return [
    'portal' => [
        'login_url' => env('HOTSPOT_LOGIN_URL'),
    ],
    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'fallback_email_domain' => env('PAYSTACK_FALLBACK_EMAIL_DOMAIN', 'hotspot.com'),
    ],
    'mikrotik' => [
        'host' => env('MIKROTIK_HOST'),
        'port' => env('MIKROTIK_PORT', 8728),
        'username' => env('MIKROTIK_USERNAME'),
        'password' => env('MIKROTIK_PASSWORD'),
        'ssl' => env('MIKROTIK_SSL', false),
        'timeout' => env('MIKROTIK_TIMEOUT', 10),
        'socket_options' => [
            'bindto' => env('MIKROTIK_BIND_TO', '0:0'),
        ],
    ],
];
