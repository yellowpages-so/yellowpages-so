<?php

return [
    'default_rate_limit' => (int) env('DEVELOPER_RATE_LIMIT', 60),
    'webhook_timeout' => (int) env('WEBHOOK_TIMEOUT', 10),
    'webhook_retry_minutes' => (int) env('WEBHOOK_RETRY_MINUTES', 10),
    'sandbox_enabled' => env('DEVELOPER_SANDBOX_ENABLED', true),

    'scopes' => [
        'businesses:read',
        'businesses:write',
        'search:read',
        'reviews:read',
        'reviews:write',
        'leads:write',
        'media:write',
        'billing:read',
        'webhooks:manage',
    ],
];
