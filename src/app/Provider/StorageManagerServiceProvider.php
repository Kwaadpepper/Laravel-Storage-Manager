<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Provider;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;

class StorageManagerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadTranslations();
        $this->loadConfig();
        $this->loadViews();
        $this->loadRoutes();
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerAliases();
        $this->registerDependenciesInjection();

    }

    private function registerDependenciesInjection(): void
    {
        $this->app->singleton(
            ConfigRepository::class,
            fn () => new ConfigRepository(
                Config::get('storage-manager'),
                Config::get('storage-manager::static-config', [])
            )
        );
    }

    public function registerAliases(): void
    {
        $this->app->alias(ConfigRepository::class, 'storage-manager.config');
    }

    private function loadConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/storage-manager.php',
            'storage-manager'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/static-config.php',
            'storage-manager::static-config'
        );
    }

    private function registerConfig(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../../config/storage-manager.php' => $this->app->configPath('storage-manager.php'),
            ],
            'storage-manager:config'
        );
    }

    private function loadTranslations(): void
    {
        $this->loadTranslationsFrom(
            __DIR__ . '/../../resources/lang',
            'storage-manager'
        );
    }

    private function registerTranslations(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../../resources/lang' => $this->app->langPath('vendor/storage-manager'),
            ],
            'storage-manager:translations'
        );
    }

    private function loadRoutes(): void
    {
        $this->loadRoutesFrom(
            __DIR__ . '/../../routes.php'
        );
    }

    private function loadViews(): void
    {
        $this->loadViewsFrom(
            __DIR__ . '/../../resources/view',
            'storage-manager'
        );
    }
}
