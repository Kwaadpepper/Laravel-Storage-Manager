<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileManager;
use Kwaadpepper\LaravelStorageManager\Provider\StorageManagerServiceProvider;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;

describe('StorageManagerServiceProvider', function (): void {
    it('boots the service provider', function (): void {
        // Given
        $providerClass = StorageManagerServiceProvider::class;

        // When
        $provider = $this->app->getProvider($providerClass);

        // Then
        expect($provider)->not->toBeNull();
    });

    it('resolves the config repository from the container', function (): void {
        // Given
        $abstract = ConfigRepository::class;

        // When
        $instance = $this->app->make($abstract);

        // Then
        expect($instance)->toBeInstanceOf(ConfigRepository::class);
    });

    it('resolves the file manager from the container', function (): void {
        // Given
        $abstract = FileManager::class;

        // When
        $instance = $this->app->make($abstract);

        // Then
        expect($instance)->toBeInstanceOf(FileManager::class);
    });
});
