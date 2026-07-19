<?php

declare(strict_types=1);

return [
    'enabled'      => filter_var(env('STORAGE_MANAGER_ENABLED', true), FILTER_VALIDATE_BOOL),

    'auth' => [
        'enabled' => filter_var(env('STORAGE_MANAGER_AUTH_ENABLED', true), FILTER_VALIDATE_BOOL),
        'guard'   => env('STORAGE_MANAGER_AUTH_GUARD', 'web'),
    ],

    'route' => [
        'prefix'     => env('STORAGE_MANAGER_ROUTE_PREFIX', 'sm'),
        'middleware' => str(env('STORAGE_MANAGER_ROUTE_MIDDLEWARE', ''))
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
    ],

    'frontend' => [
        'dev_server' => [
            'enabled' => filter_var(env('STORAGE_MANAGER_USE_DEV_SERVER', false), FILTER_VALIDATE_BOOL),
            'url'     => env('STORAGE_MANAGER_DEV_SERVER_URL', 'http://localhost:5173'),
        ],
    ],

    'disks'      => [
        'default'       => env('STORAGE_MANAGER_DEFAULT_DISK', 'local'),
        'available'     => str(env('STORAGE_MANAGER_DISKS', ''))
            ->explode(',')->map(
                fn (string $diskName) => mb_trim($diskName)
            )->filter()->values()->all(),
    ],
];
