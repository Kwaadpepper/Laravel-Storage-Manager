<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kwaadpepper\LaravelStorageManager\Http\Controller\DiskController;
use Kwaadpepper\LaravelStorageManager\Http\Middleware\StorageManager;
use Kwaadpepper\LaravelStorageManager\Http\Middleware\StorageManagerApi;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;

$config = resolve(ConfigRepository::class);

if (! $config->isEnabled()) {
    return;
}

Route::group([
    'middleware' => array_merge(
        $config->getRouteMiddleware(),
        [StorageManager::class],
    ),
    'prefix' => $config->getRoutePrefix(),
    'as'     => 'storage-manager.',
], function (): void {

    Route::view('/', 'storage-manager::file-manager')->name('file-manager');

    Route::name('api.')
        ->middleware([StorageManagerApi::class])
        ->group(function (): void {
            Route::get('/disks', [DiskController::class, 'list'])->name('disks.list');
            Route::get('/disks/select', [DiskController::class, 'select'])->name('disks.select');
        });

});
