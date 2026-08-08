<?php

declare(strict_types=1);

return [
    'storage' => [
        'default_limit' => env('FILEHOSTING_DEFAULT_QUOTA', 10737418240),
        'admin_limit' => null,
        'warn_percentage' => 90,
    ],

    'upload' => [
        'max_size' => env('FILEHOSTING_MAX_UPLOAD_SIZE', 104857600), // 100 MB
    ],

    'embed' => [
        'width' => 640,
        'height' => 360,
    ],

    'link' => [
        'default_expiry_hours' => 24,
    ],

    'trash' => [
        'retention_days' => 30,
    ],

    'log' => [
        'retention_days' => 90,
    ],

    'player' => [
        'driver' => env('PLAYER_DRIVER', 'plyr'),
        'signed_url_ttl_minutes' => 30,
    ],

    'subtitle' => [
        'allowed_extensions' => ['vtt', 'srt'],
        'max_size' => 2097152, // 2 MB
    ],
];
