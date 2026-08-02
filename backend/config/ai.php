<?php

return [
    'provider' => env('AI_PROVIDER', 'local'),
    'base_url' => env('AI_BASE_URL'),
    'api_key' => env('AI_API_KEY'),
    'model' => env('AI_MODEL', 'local-rules-v1'),
    'timeout' => (int) env('AI_TIMEOUT', 30),
    'cache_hours' => (int) env('AI_CACHE_HOURS', 24),
    'enabled' => env('AI_ENABLED', true),
];
