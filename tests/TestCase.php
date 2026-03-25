<?php

declare(strict_types=1);

namespace Tests;

use Kwaadpepper\LaravelStorageManager\Provider\StorageManagerServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            StorageManagerServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('filesystems.disks.local', [
            'driver' => 'local',
            'root'   => storage_path('app'),
        ]);

        $app['config']->set('storage-manager.disks.default', 'local');
        $app['config']->set('storage-manager.disks.available', ['local']);
    }
}
