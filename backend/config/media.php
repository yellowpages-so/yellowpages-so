<?php

return [
    'disk' => env('MEDIA_DISK', 'public'),
    'max_image_mb' => (int) env('MEDIA_MAX_IMAGE_MB', 10),
    'max_document_mb' => (int) env('MEDIA_MAX_DOCUMENT_MB', 25),
    'max_video_mb' => (int) env('MEDIA_MAX_VIDEO_MB', 100),
    'signed_url_minutes' => (int) env('MEDIA_SIGNED_URL_MINUTES', 30),

    'allowed_image_mimes' => [
        'image/jpeg',
        'image/png',
        'image/webp',
    ],

    'allowed_document_mimes' => [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ],

    'allowed_video_mimes' => [
        'video/mp4',
        'video/webm',
    ],

    'collections' => [
        'logo',
        'cover',
        'gallery',
        'review',
        'advertising',
        'verification',
        'document',
        'video',
    ],
];
