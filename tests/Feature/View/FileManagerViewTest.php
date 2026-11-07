<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Provider\StorageManagerServiceProvider;

describe('FileManagerView', function (): void {
    it('renders script tag with nonce attribute when cspNonce is configured', function (): void {
        // GIVEN
        Config::set('storage-manager.frontend.csp_nonce', 'test-csp-nonce-456');
        App::forgetInstance(StorageManagerConfig::class);

        // WHEN
        $rendered = View::make('storage-manager::file-manager')->render();

        // THEN
        expect($rendered)->toContain('nonce="test-csp-nonce-456"');
    });

    it('renders script tag without nonce attribute when cspNonce is not configured', function (): void {
        // GIVEN
        Config::set('storage-manager.frontend.csp_nonce', '');
        App::forgetInstance(StorageManagerConfig::class);

        // WHEN
        $rendered = View::make('storage-manager::file-manager')->render();

        // THEN
        expect($rendered)->not->toContain('nonce=');
    });
})->covers(StorageManagerServiceProvider::class);
