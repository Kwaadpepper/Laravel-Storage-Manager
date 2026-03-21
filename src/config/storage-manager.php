<?php

declare(strict_types=1);

return [
    'enabled'      => env('STORAGE_MANAGER_ENABLED', true),

    'auth' => [
        'enabled' => env('STORAGE_MANAGER_AUTH_ENABLED', true),
        'guard'   => env('STORAGE_MANAGER_AUTH_GUARD', 'web'),
    ],

    'route' => [
        'prefix'     => env('STORAGE_MANAGER_ROUTE_PREFIX', 'sm'),
        'middleware' => str(env('STORAGE_MANAGER_ROUTE_MIDDLEWARE', ''))
            ->explode(',')->map(
                fn (string $middleware) => mb_trim($middleware)
            )->filter()->values()->all(),

    ],

    'disks'      => [
        'default'       => env('STORAGE_MANAGER_DEFAULT_DISK', 'local'),
        'available'     => str(env('STORAGE_MANAGER_DISKS', ''))
            ->explode(',')->map(
                fn (string $diskName) => mb_trim($diskName)
            )->filter()->values()->all(),
    ],
];
