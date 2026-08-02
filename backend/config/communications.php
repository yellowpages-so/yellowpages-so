<?php

return [
    'default_channels' => ['in_app', 'email'],
    'max_attempts' => (int) env('NOTIFICATION_MAX_ATTEMPTS', 3),
    'retry_minutes' => (int) env('NOTIFICATION_RETRY_MINUTES', 10),

    'providers' => [
        'email' => env('NOTIFICATION_EMAIL_PROVIDER', 'log'),
        'sms' => env('NOTIFICATION_SMS_PROVIDER', 'log'),
        'whatsapp' => env('NOTIFICATION_WHATSAPP_PROVIDER', 'log'),
        'push' => env('NOTIFICATION_PUSH_PROVIDER', 'log'),
    ],
];
