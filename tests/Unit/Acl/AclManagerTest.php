<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate as GateFacade;
use Kwaadpepper\LaravelStorageManager\Acl\AclManager;
use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

describe('AclManager', function (): void {
    it('allows when allow_by_default is enabled and disk is writable', function (): void {
        // GIVEN
        Config::set('storage-manager.auth.allow_by_default', true);
        App::forgetInstance(StorageManagerConfig::class);
        $config     = resolve(StorageManagerConfig::class);
        $aclManager = new AclManager($config);
        $disk       = new Disk('local', 'local', readOnly: false);
        $path       = new Path('/test.txt');

        // WHEN
        $allowed = $aclManager->allows(SmAbility::DELETE, null, $disk, $path);

        // THEN
        expect($allowed)->toBeTrue();
    });

    it('denies mutations on readonly disks', function (): void {
        // GIVEN
        Config::set('storage-manager.auth.allow_by_default', true);
        App::forgetInstance(StorageManagerConfig::class);
        $config     = resolve(StorageManagerConfig::class);
        $aclManager = new AclManager($config);
        $disk       = new Disk('readonly_disk', 'local', readOnly: true);
        $path       = new Path('/test.txt');

        // WHEN
        $allowed = $aclManager->allows(SmAbility::DELETE, null, $disk, $path);

        // THEN
        expect($allowed)->toBeFalse();
    });

    it('allows read queries on readonly disks', function (): void {
        // GIVEN
        Config::set('storage-manager.auth.allow_by_default', true);
        App::forgetInstance(StorageManagerConfig::class);
        $config     = resolve(StorageManagerConfig::class);
        $aclManager = new AclManager($config);
        $disk       = new Disk('readonly_disk', 'local', readOnly: true);
        $path       = new Path('/test.txt');

        // WHEN
        $allowed = $aclManager->allows(SmAbility::VIEW, null, $disk, $path);

        // THEN
        expect($allowed)->toBeTrue();
    });

    it('denies when auth is enabled and user is null', function (): void {
        // GIVEN
        Config::set('storage-manager.auth.enabled', true);
        Config::set('storage-manager.auth.allow_by_default', true);
        App::forgetInstance(StorageManagerConfig::class);
        $config     = resolve(StorageManagerConfig::class);
        $aclManager = new AclManager($config);
        $disk       = new Disk('local', 'local');

        // WHEN
        $allowed = $aclManager->allows(SmAbility::VIEW, null, $disk);

        // THEN
        expect($allowed)->toBeFalse();
    });

    it('denies authenticated users when allow_by_default is disabled', function (): void {
        // GIVEN
        Config::set('storage-manager.auth.enabled', true);
        Config::set('storage-manager.auth.allow_by_default', false);
        App::forgetInstance(StorageManagerConfig::class);
        $config     = resolve(StorageManagerConfig::class);
        $aclManager = new AclManager($config);
        $user       = \Mockery::mock(Authenticatable::class);
        $disk       = new Disk('local', 'local');

        // WHEN
        $allowed = $aclManager->allows(SmAbility::VIEW, $user, $disk);

        // THEN
        expect($allowed)->toBeFalse();
    });

    it('allows authenticated users when allow_by_default is enabled', function (): void {
        // GIVEN
        Config::set('storage-manager.auth.enabled', true);
        Config::set('storage-manager.auth.allow_by_default', true);
        App::forgetInstance(StorageManagerConfig::class);
        $config     = resolve(StorageManagerConfig::class);
        $aclManager = new AclManager($config);
        $user       = \Mockery::mock(Authenticatable::class);
        $disk       = new Disk('local', 'local');

        // WHEN
        $allowed = $aclManager->allows(SmAbility::VIEW, $user, $disk);

        // THEN
        expect($allowed)->toBeTrue();
    });

    it('registers default gates for all abilities on Gate instance', function (): void {
        // GIVEN
        $config     = resolve(StorageManagerConfig::class);
        $aclManager = new AclManager($config);
        $gate       = \Mockery::mock(Gate::class);

        foreach (SmAbility::ALL as $ability) {
            $gate->shouldReceive('has')->with($ability)->once()->andReturn(false);
            $gate->shouldReceive('define')->with($ability, \Mockery::type('Closure'))->once();
        }

        // WHEN
        $aclManager->registerDefaultGates($gate);

        // THEN
        expect(true)->toBeTrue();
    });

    it('executes custom 3-parameter gate definition with null path without ArgumentCountError', function (): void {
        // GIVEN
        $disk = new Disk('local', 'local');
        GateFacade::define('test-custom-ability', function (?Authenticatable $user, Disk $d, ?Path $p): bool {
            return $d->name === 'local' && $p === null;
        });

        // WHEN
        $result = GateFacade::check('test-custom-ability', [$disk, null]);

        // THEN
        expect($result)->toBeTrue();
    });
})->covers(AclManager::class);
