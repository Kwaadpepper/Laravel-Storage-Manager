<?php

declare(strict_types=1);

return [
    'enabled' => env('STORAGE_MANAGER_ENABLED', true),

    'auth' => [
        'enabled' => env('STORAGE_MANAGER_AUTH_ENABLED', true),
        'guard'   => env('STORAGE_MANAGER_AUTH_GUARD', 'web'),
    ],

    'route' => [
        'prefix'     => env('STORAGE_MANAGER_ROUTE_PREFIX', 'sm'),
        'middleware' => env('STORAGE_MANAGER_ROUTE_MIDDLEWARE', ''),
    ],

    'upload' => [
        'chunk_min_size'     => env('STORAGE_MANAGER_UPLOAD_CHUNK_MIN_SIZE', 2 * 1024 * 1024),   // 2 MB
        'chunk_max_size'     => env('STORAGE_MANAGER_UPLOAD_CHUNK_MAX_SIZE', 20 * 1024 * 1024),  // 20 MB
        'temp_disk'          => env('STORAGE_MANAGER_UPLOAD_TEMP_DISK', null),
        'temp_path'          => env('STORAGE_MANAGER_UPLOAD_TEMP_PATH', null),
        'duplicate_policy'   => env('STORAGE_MANAGER_UPLOAD_DUPLICATE_POLICY', 'auto_rename'),
        'sanitize_filenames' => env('STORAGE_MANAGER_UPLOAD_SANITIZE_FILENAMES', true),
        'lock_driver'        => env('STORAGE_MANAGER_UPLOAD_LOCK_DRIVER', 'auto'),
        'allowed_extensions' => env(
            'STORAGE_MANAGER_ALLOWED_EXTENSIONS',
            'jpg,jpeg,png,gif,webp,avif,pdf,mp4,webm,mp3,wav,ogg,txt,csv,doc,docx,xls,xlsx,zip'
        ),
    ],

    'frontend' => [
        'csp_nonce'  => env('STORAGE_MANAGER_CSP_NONCE', null),
        'dev_server' => [
            'enabled' => env('STORAGE_MANAGER_USE_DEV_SERVER', false),
            'url'     => env('STORAGE_MANAGER_DEV_SERVER_URL', 'http://localhost:5173'),
        ],
    ],

    'disks' => [
        'default'   => env('STORAGE_MANAGER_DEFAULT_DISK', 'local'),
        'available' => env('STORAGE_MANAGER_DISKS', ''),
        'configs'   => [
            // Example per-disk custom configuration:
            // 's3_public' => [
            //     'root'               => '/public-assets',
            //     'allowed_extensions' => ['jpg', 'png', 'webp', 'mp4'],
            //     'trash'              => false,
            //     'max_file_size'      => 100 * 1024 * 1024,
            //     'filename_policy'    => 'permissive',
            //     'read_only'          => false,
            //     'quota'              => 50 * 1024 * 1024 * 1024,
            //     'duplicate_policy'   => 'auto_rename',
            // ],
        ],
    ],

    'thumbnails' => [
        'enabled'        => env('STORAGE_MANAGER_THUMBNAILS_ENABLED', true),
        'cache_duration' => env('STORAGE_MANAGER_THUMBNAILS_CACHE_DURATION', 604800),
        'width'          => env('STORAGE_MANAGER_THUMBNAILS_WIDTH', 160),
        'height'         => env('STORAGE_MANAGER_THUMBNAILS_HEIGHT', 160),
        'quality'        => env('STORAGE_MANAGER_THUMBNAILS_QUALITY', 80),
    ],

    'stats' => [
        'enabled'                    => env('STORAGE_MANAGER_STATS_ENABLED', true),
        'cache_duration'             => env('STORAGE_MANAGER_STATS_CACHE_DURATION', 60),
        'realtime_remote_usage'      => env('STORAGE_MANAGER_STATS_REALTIME_REMOTE_USAGE', false),
        'usage_cache_duration'       => env('STORAGE_MANAGER_STATS_USAGE_CACHE_DURATION', 86400),
        'schedule_usage_calculation' => env('STORAGE_MANAGER_STATS_SCHEDULE_USAGE_CALCULATION', true),
        'quotas'                     => [
            // 'local' => 10 * 1024 * 1024 * 1024,
        ],
    ],

    'trash' => [
        'enabled'        => env('STORAGE_MANAGER_TRASH_ENABLED', true),
        'folder'         => env('STORAGE_MANAGER_TRASH_FOLDER', '.trash'),
        'retention_days' => env('STORAGE_MANAGER_TRASH_RETENTION_DAYS', 30),
    ],
];
