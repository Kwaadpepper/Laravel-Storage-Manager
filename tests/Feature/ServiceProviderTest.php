<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileManager;
use Kwaadpepper\LaravelStorageManager\Provider\StorageManagerServiceProvider;

describe('StorageManagerServiceProvider', function (): void {
    it('boots the service provider', function (): void {
        // GIVEN
        $providerClass = StorageManagerServiceProvider::class;

        // WHEN
        $provider = $this->app->getProvider($providerClass);

        // THEN
        expect($provider)->not->toBeNull();
    });

    it('resolves the config repository from the container', function (): void {
        // GIVEN
        $abstract = StorageManagerConfig::class;

        // WHEN
        $instance = $this->app->make($abstract);

        // THEN
        expect($instance)->toBeInstanceOf(StorageManagerConfig::class);
    });

    it('resolves the file manager from the container', function (): void {
        // GIVEN
        $abstract = FileManager::class;

        // WHEN
        $instance = $this->app->make($abstract);

        // THEN
        expect($instance)->toBeInstanceOf(FileManager::class);
    });
});
