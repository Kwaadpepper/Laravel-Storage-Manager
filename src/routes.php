<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kwaadpepper\LaravelStorageManager\Http\Controller\DiskController;
use Kwaadpepper\LaravelStorageManager\Http\Controller\FileManagerController;
use Kwaadpepper\LaravelStorageManager\Http\Middleware\StorageManagerApi;
use Kwaadpepper\LaravelStorageManager\Http\Middleware\StorageManagerMiddleware;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;

$config = resolve(ConfigRepository::class);

if (! $config->isEnabled()) {
    return;
}

Route::group([
    'middleware' => array_merge(
        $config->getRouteMiddleware(),
        [
            StorageManagerMiddleware::class,
            $config->isAuthEnabled() ? 'auth:' . $config->getAuthGuard() : null,
        ],
    ),
    'prefix' => $config->getRoutePrefix(),
    'as'     => 'storage-manager.',
], function (): void {

    Route::view('/', 'storage-manager::file-manager')->name('file-manager');

    Route::name('api.')->middleware([StorageManagerApi::class])->group(function (): void {

        Route::group([
            'prefix' => 'fm',
            'as'     => 'fm.',
        ], function (): void {
            Route::get('/init', [FileManagerController::class, 'init'])->name('init');
        });

        Route::group([
            'prefix' => 'disks',
            'as'     => 'disks.',
        ], function (): void {
            Route::get('/', [DiskController::class, 'list'])->name('list');
            Route::get('/select', [DiskController::class, 'select'])->name('select');
        });

    });

});
