<?php

return [
    'driver' => env('SEARCH_DRIVER', 'meilisearch'),
    'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
    'key' => env('MEILISEARCH_KEY', ''),
    'index' => env('MEILISEARCH_BUSINESSES_INDEX', 'businesses'),
    'fallback_to_database' => env('SEARCH_DATABASE_FALLBACK', true),
];
