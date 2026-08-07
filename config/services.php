<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'torrent' => [
        'host' => env('TRANSMISSION_HOST', 'http://127.0.0.1'),
        'port' => env('TRANSMISSION_PORT', '9091'),
        'username' => env('TRANSMISSION_USER', 'admin'),
        'password' => env('TRANSMISSION_PASS', ''),
        // Host-side shared volume where downloaded torrents are written.
        'download_dir' => env('TORRENT_DOWNLOAD_DIR', base_path('storage/downloader/incoming')),
        // Path as seen inside the Transmission container (used for savepath).
        'container_download_dir' => env('TORRENT_CONTAINER_DOWNLOAD_DIR', '/downloads'),
    ],

    'downloads' => [
        'retention_days' => env('DOWNLOAD_RETENTION_DAYS', 7),
    ],

];
