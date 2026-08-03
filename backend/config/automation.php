<?php

return [
    'max_attempts' => (int) env('AUTOMATION_MAX_ATTEMPTS', 5),
    'retry_minutes' => (int) env('AUTOMATION_RETRY_MINUTES', 5),
    'batch_size' => (int) env('AUTOMATION_BATCH_SIZE', 100),
    'default_timezone' => env(
        'AUTOMATION_DEFAULT_TIMEZONE',
        'Africa/Mogadishu'
    ),
];
