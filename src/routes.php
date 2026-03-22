<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kwaadpepper\LaravelStorageManager\Http\Controller\BasicOperationsController;
use Kwaadpepper\LaravelStorageManager\Http\Controller\DiskController;
use Kwaadpepper\LaravelStorageManager\Http\Controller\FileManagerController;
use Kwaadpepper\LaravelStorageManager\Http\Middleware\StorageManagerApiMiddleware;
use Kwaadpepper\LaravelStorageManager\Http\Middleware\StorageManagerMiddleware;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;

$config = resolve(ConfigRepository::class);

if (! $config->isEnabled()) {
    return;
}

$middlewares   = $config->getRouteMiddleware();
$middlewares[] = StorageManagerMiddleware::class;

if ($config->isAuthEnabled()) {
    $middlewares[] = 'auth:' . $config->getAuthGuard();
}

Route::group([
    'middleware' => $middlewares,
    'prefix'     => $config->getRoutePrefix(),
    'as'         => 'storage-manager.',
], function (): void {

    Route::view('/', 'storage-manager::file-manager')->name('file-manager');

    Route::name('api.')->middleware([StorageManagerApiMiddleware::class])->group(function (): void {

        Route::group([
            'prefix' => 'fm',
            'as'     => 'fm.',
        ], function (): void {
            // File Manager routes
            Route::get('/init', [FileManagerController::class, 'init'])->name('init');
            Route::get('/tree', [FileManagerController::class, 'tree'])->name('tree');
            Route::get('/content', [FileManagerController::class, 'content'])->name('content');

            // Basic operations routes
            Route::post('/create-directory', [BasicOperationsController::class, 'createDirectory'])->name('create-directory');
            Route::delete('/delete', [BasicOperationsController::class, 'delete'])->name('delete');
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
