<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Contract\Executor;
use Kwaadpepper\LaravelStorageManager\Provider\StorageManagerServiceProvider;
use Kwaadpepper\LaravelStorageManager\Service\ActionExecutorService;

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

    it('resolves the executor from the container', function (): void {
        // GIVEN
        $abstract = Executor::class;

        // WHEN
        $instance = $this->app->make($abstract);

        // THEN
        expect($instance)->toBeInstanceOf(ActionExecutorService::class);
    });
})->covers(StorageManagerServiceProvider::class);
