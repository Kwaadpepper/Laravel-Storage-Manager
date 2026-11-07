<?php

declare(strict_types=1);

use Illuminate\Support\Str;

return [
    'enabled'      => filter_var(env('STORAGE_MANAGER_ENABLED', true), FILTER_VALIDATE_BOOL),

    'auth' => [
        'enabled' => filter_var(env('STORAGE_MANAGER_AUTH_ENABLED', true), FILTER_VALIDATE_BOOL),
        'guard'   => env('STORAGE_MANAGER_AUTH_GUARD', 'web'),
    ],

    'route' => [
        'prefix'     => env('STORAGE_MANAGER_ROUTE_PREFIX', 'sm'),
        'middleware' => Str::of(env('STORAGE_MANAGER_ROUTE_MIDDLEWARE', ''))
            ->explode(',')->map(
                fn (string $middleware) => mb_trim($middleware)
            )->filter()->values()->all(),

    ],

    'upload' => [
        'chunk_min_size'      => (int) env('STORAGE_MANAGER_UPLOAD_CHUNK_MIN_SIZE', 2 * 1024 * 1024),   // 2 MB
        'chunk_max_size'      => (int) env('STORAGE_MANAGER_UPLOAD_CHUNK_MAX_SIZE', 20 * 1024 * 1024),  // 20 MB
        'temp_disk'           => env('STORAGE_MANAGER_UPLOAD_TEMP_DISK', null),
        'temp_path'           => env('STORAGE_MANAGER_UPLOAD_TEMP_PATH', null),
        'duplicate_policy'    => env('STORAGE_MANAGER_UPLOAD_DUPLICATE_POLICY', 'auto_rename'),
        'sanitize_filenames'  => filter_var(env('STORAGE_MANAGER_UPLOAD_SANITIZE_FILENAMES', true), FILTER_VALIDATE_BOOL),
        'allowed_extensions'  => Str::of(env(
            'STORAGE_MANAGER_ALLOWED_EXTENSIONS',
            'jpg,jpeg,png,gif,webp,avif,pdf,mp4,webm,mp3,wav,ogg,txt,csv,doc,docx,xls,xlsx,zip'
        ))->explode(',')->map(fn (string $ext): string => strtolower(mb_trim($ext)))->filter()->values()->all(),
    ],

    'frontend' => [
        'csp_nonce'  => env('STORAGE_MANAGER_CSP_NONCE', null),
        'dev_server' => [
            'enabled' => filter_var(env('STORAGE_MANAGER_USE_DEV_SERVER', false), FILTER_VALIDATE_BOOL),
            'url'     => env('STORAGE_MANAGER_DEV_SERVER_URL', 'http://localhost:5173'),
        ],
    ],

    'disks'      => [
        'default'       => env('STORAGE_MANAGER_DEFAULT_DISK', 'local'),
        'available'     => Str::of(env('STORAGE_MANAGER_DISKS', ''))
            ->explode(',')->map(
                fn (string $diskName) => mb_trim($diskName)
            )->filter()->values()->all(),
    ],

    'thumbnails' => [
        'enabled'        => filter_var(env('STORAGE_MANAGER_THUMBNAILS_ENABLED', true), FILTER_VALIDATE_BOOL),
        'cache_duration' => (int) env('STORAGE_MANAGER_THUMBNAILS_CACHE_DURATION', 604800),
        'width'          => (int) env('STORAGE_MANAGER_THUMBNAILS_WIDTH', 160),
        'height'         => (int) env('STORAGE_MANAGER_THUMBNAILS_HEIGHT', 160),
        'quality'        => (int) env('STORAGE_MANAGER_THUMBNAILS_QUALITY', 80),
    ],

    'stats' => [
        'enabled'        => filter_var(env('STORAGE_MANAGER_STATS_ENABLED', true), FILTER_VALIDATE_BOOL),
        'cache_duration' => (int) env('STORAGE_MANAGER_STATS_CACHE_DURATION', 60),
        'quotas'         => [
            // 'local' => 10 * 1024 * 1024 * 1024,
        ],
    ],

    'trash' => [
        'enabled'        => filter_var(env('STORAGE_MANAGER_TRASH_ENABLED', true), FILTER_VALIDATE_BOOL),
        'folder'         => env('STORAGE_MANAGER_TRASH_FOLDER', '.trash'),
        'retention_days' => (int) env('STORAGE_MANAGER_TRASH_RETENTION_DAYS', 30),
    ],
];
