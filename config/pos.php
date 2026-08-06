<?php

return [
    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 5),
    'gcash' => [
        'number' => env('GCASH_NUMBER', '09536774000'),
        'qr_image' => env('GCASH_QR_IMAGE', 'images/gcash-qr.jpg'),
    ],
    'paymongo' => [
        'secret_key_test' => env('PAYMONGO_SECRET_KEY_TEST', ''),
        'secret_key_live' => env('PAYMONGO_SECRET_KEY_LIVE', ''),
        'use_test' => env('PAYMONGO_USE_TEST', true),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET', ''),
    ],
    'gmail' => [
        'user' => env('GMAIL_USER', 'admincapj@gmail.com'),
        'client_id' => env('GMAIL_CLIENT_ID', ''),
        'client_secret' => env('GMAIL_CLIENT_SECRET', ''),
        'refresh_token' => env('GMAIL_REFRESH_TOKEN', ''),
        'admin_email' => env('ADMIN_EMAIL', 'admincapj@gmail.com'),
        'admin_name' => env('ADMIN_NAME', 'Captain J Admin'),
    ],
];
