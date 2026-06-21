<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kwaadpepper\LaravelStorageManager\Http\Controller\AssetController;
use Kwaadpepper\LaravelStorageManager\Http\Controller\BasicOperationsController;
use Kwaadpepper\LaravelStorageManager\Http\Controller\DiskController;
use Kwaadpepper\LaravelStorageManager\Http\Controller\DownloadController;
use Kwaadpepper\LaravelStorageManager\Http\Controller\FileManagerController;
use Kwaadpepper\LaravelStorageManager\Http\Controller\UploadController;
use Kwaadpepper\LaravelStorageManager\Http\Middleware\StorageManagerApiMiddleware;
use Kwaadpepper\LaravelStorageManager\Http\Middleware\StorageManagerMiddleware;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;

$config = resolve(ConfigRepository::class);

if (! $config->isEnabled()) {
    return;
}

$middlewares   = $config->getRouteMiddleware();
$middlewares[] = StorageManagerMiddleware::class;
$middlewares[] = \Illuminate\Routing\Middleware\SubstituteBindings::class;

if ($config->isAuthEnabled()) {
    $middlewares[] = 'auth:' . $config->getAuthGuard();
}

Route::group([
    'middleware' => $middlewares,
    'prefix'     => $config->getRoutePrefix(),
    'as'         => 'storage-manager.',
], function (): void {

    Route::view('/', 'storage-manager::file-manager')->name('file-manager');
    Route::get('assets/{dir}/{file}', AssetController::class)->name('assets');

    Route::get('download/{disk}/{path}', DownloadController::class)->where('path', '.*')->name('download');

    Route::name('api.')->middleware([StorageManagerApiMiddleware::class])->group(function (): void {

        Route::group([
            'prefix' => 'fm',
            'as'     => 'fm.',
        ], function (): void {
            // File Manager routes
            Route::get('init', [FileManagerController::class, 'init'])->name('init');
            Route::get('tree', [FileManagerController::class, 'tree'])->name('tree');
            Route::get('content', [FileManagerController::class, 'content'])->name('content');

            // Basic operations routes
            Route::get('properties', [BasicOperationsController::class, 'properties'])->name('properties');
            Route::get('exists', [BasicOperationsController::class, 'exists'])->name('exists');
            Route::post('create-directory', [BasicOperationsController::class, 'createDirectory'])->name('create-directory');
            Route::post('create-file', [BasicOperationsController::class, 'createFile'])->name('create-file');
            Route::delete('delete', [BasicOperationsController::class, 'delete'])->name('delete');
            Route::put('rename', [BasicOperationsController::class, 'rename'])->name('rename');
            Route::post('copy', [BasicOperationsController::class, 'copy'])->name('copy');
            Route::post('move', [BasicOperationsController::class, 'move'])->name('move');

            // Upload routes
            Route::group([
                'prefix' => 'upload',
                'as'     => 'upload.',
            ], function (): void {
                Route::post('init', [UploadController::class, 'init'])->name('init');
                Route::post('chunk', [UploadController::class, 'chunk'])->name('chunk');
                Route::post('complete', [UploadController::class, 'complete'])->name('complete');
                Route::get('status/{uploadId}', [UploadController::class, 'status'])->name('status');
            });
        });

        Route::group([
            'prefix' => 'disks',
            'as'     => 'disks.',
        ], function (): void {
            Route::get('/', [DiskController::class, 'list'])->name('list');
            Route::post('select', [DiskController::class, 'select'])->name('select');
        });
    });
});
