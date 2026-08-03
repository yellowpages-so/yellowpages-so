<?php

return [
    'default_provider' => env('PAYMENTS_DEFAULT_PROVIDER', 'manual'),
    'currency' => env('PAYMENTS_DEFAULT_CURRENCY', 'USD'),
    'intent_expiry_minutes' => (int) env('PAYMENTS_INTENT_EXPIRY_MINUTES', 30),
    'webhook_tolerance_seconds' => (int) env('PAYMENTS_WEBHOOK_TOLERANCE_SECONDS', 300),
];
