<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Config\DisksConfig;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

describe('DisksConfig', function (): void {
    it('resolves direct driver correctly', function (): void {
        // GIVEN
        $config = new DisksConfig(
            available: ['local'],
            default: 'local',
            filesystemsDisks: [
                'local' => ['driver' => 'local'],
            ],
        );

        // WHEN
        $driver = $config->resolveDriver('local');

        // THEN
        expect($driver)->toBe('local');
    });

    it('resolves scoped driver chain to parent driver', function (): void {
        // GIVEN
        $config = new DisksConfig(
            available: ['scoped_disk'],
            default: 'scoped_disk',
            filesystemsDisks: [
                'scoped_disk' => ['driver' => 'scoped', 'disk' => 'parent_disk'],
                'parent_disk' => ['driver' => 'local'],
            ],
        );

        // WHEN
        $driver = $config->resolveDriver('scoped_disk');

        // THEN
        expect($driver)->toBe('local');
    });

    it('throws exception when scoped disk has missing parent disk configuration', function (): void {
        // GIVEN
        $config = new DisksConfig(
            available: ['scoped_disk'],
            default: 'scoped_disk',
            filesystemsDisks: [
                'scoped_disk' => ['driver' => 'scoped'],
            ],
        );

        // GIVEN
        $expectedMessage = "Scoped disk 'scoped_disk' is missing a valid parent 'disk' configuration.";

        // WHEN
        $closure = fn (): string => $config->resolveDriver('scoped_disk');

        // THEN
        expect($closure)->toThrow(\UnexpectedValueException::class, $expectedMessage);
    });

    it('throws exception when circular reference is detected in scoped disks', function (): void {
        // GIVEN
        $config = new DisksConfig(
            available: ['disk_a'],
            default: 'disk_a',
            filesystemsDisks: [
                'disk_a' => ['driver' => 'scoped', 'disk' => 'disk_b'],
                'disk_b' => ['driver' => 'scoped', 'disk' => 'disk_a'],
            ],
        );

        // WHEN
        $closure = fn (): string => $config->resolveDriver('disk_a');

        // THEN
        expect($closure)->toThrow(\UnexpectedValueException::class, "Circular reference detected in scoped disk 'disk_b'.");
    });

    it('throws exception when parent disk of scoped disk has no valid driver', function (): void {
        // GIVEN
        $config = new DisksConfig(
            available: ['scoped_disk'],
            default: 'scoped_disk',
            filesystemsDisks: [
                'scoped_disk' => ['driver' => 'scoped', 'disk' => 'parent_disk'],
                'parent_disk' => [],
            ],
        );

        // WHEN
        $closure = fn (): string => $config->resolveDriver('scoped_disk');

        // THEN
        expect($closure)->toThrow(\UnexpectedValueException::class, "Parent disk 'parent_disk' of scoped disk 'scoped_disk' has no valid driver configured.");
    });

    it('retrieves default disk and falls back to first disk', function (): void {
        // GIVEN
        $disk1             = new Disk('local', 'local');
        $disk2             = new Disk('s3', 's3');
        $configWithDefault = new DisksConfig(
            available: ['local', 's3'],
            default: 's3',
            disks: ['local' => $disk1, 's3' => $disk2],
        );
        $configWithoutDefault = new DisksConfig(
            available: ['local', 's3'],
            default: null,
            disks: ['local' => $disk1, 's3' => $disk2],
        );
        $emptyConfig = new DisksConfig(
            available: [],
            default: null,
            disks: [],
        );

        // WHEN
        $defaultDisk  = $configWithDefault->getDefaultDisk();
        $fallbackDisk = $configWithoutDefault->getDefaultDisk();
        $nullDisk     = $emptyConfig->getDefaultDisk();

        // THEN
        expect($defaultDisk)->toBe($disk2);
        expect($fallbackDisk)->toBe($disk1);
        expect($nullDisk)->toBeNull();
    });

    it('retrieves specific disk and returns disks map', function (): void {
        // GIVEN
        $disk   = new Disk('local', 'local');
        $config = new DisksConfig(
            available: ['local'],
            default: 'local',
            disks: ['local' => $disk],
        );

        // WHEN
        $found   = $config->getDisk('local');
        $missing = $config->getDisk('missing');
        $map     = $config->getDisksMap();

        // THEN
        expect($found)->toBe($disk);
        expect($missing)->toBeNull();
        expect($map)->toBe(['local' => $disk]);
    });
})->covers(DisksConfig::class);
