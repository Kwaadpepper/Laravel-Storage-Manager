<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Config\UploadConfig;
use Kwaadpepper\LaravelStorageManager\Enum\DuplicatePolicy;
use Kwaadpepper\LaravelStorageManager\Enum\FilenamePolicy;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

describe('Disk', function (): void {
    it('stores all constructor arguments as readonly properties', function (): void {
        // GIVEN
        $driver   = 'local';
        $name     = 'my-disk';
        $throw    = true;
        $report   = false;
        $readOnly = true;

        // WHEN
        $disk = new Disk($driver, $name, $throw, $report, $readOnly);

        // THEN
        expect($disk->driver)->toBe($driver);
        expect($disk->name)->toBe($name);
        expect($disk->throw)->toBeTrue();
        expect($disk->report)->toBeFalse();
        expect($disk->readOnly)->toBeTrue();
    });

    it('stores per-disk configuration options', function (): void {
        // GIVEN
        $driver             = 's3';
        $name               = 's3_public';
        $root               = '/public-assets/';
        $allowedExtensions  = ['jpg', 'png', 'webp'];
        $trash              = false;
        $trashFolder        = '.my-trash';
        $trashRetentionDays = 14;
        $maxFileSize        = 100 * 1024 * 1024;
        $quota              = 50  * 1024 * 1024 * 1024;
        $duplicatePolicy    = DuplicatePolicy::AUTO_RENAME;
        $filenamePolicy     = FilenamePolicy::PERMISSIVE;

        // WHEN
        $disk = new Disk(
            driver: $driver,
            name: $name,
            root: $root,
            allowedExtensions: $allowedExtensions,
            trash: $trash,
            trashFolder: $trashFolder,
            trashRetentionDays: $trashRetentionDays,
            maxFileSize: $maxFileSize,
            quota: $quota,
            duplicatePolicy: $duplicatePolicy,
            filenamePolicy: $filenamePolicy,
        );

        // THEN
        expect($disk->root)->toBe('/public-assets');
        expect($disk->allowedExtensions)->toBe(['jpg', 'png', 'webp']);
        expect($disk->trash)->toBeFalse();
        expect($disk->trashFolder)->toBe('.my-trash');
        expect($disk->trashRetentionDays)->toBe(14);
        expect($disk->maxFileSize)->toBe(104857600);
        expect($disk->quota)->toBe(53687091200);
        expect($disk->duplicatePolicy)->toBe(DuplicatePolicy::AUTO_RENAME);
        expect($disk->filenamePolicy)->toBe(FilenamePolicy::PERMISSIVE);
        expect($disk->isRemote())->toBeTrue();
        expect($disk->isLocal())->toBeFalse();
    });

    it('normalizes root correctly', function (): void {
        // GIVEN
        $emptyRootDisk = new Disk('local', 'diskA', root: '');
        $slashRootDisk = new Disk('local', 'diskB', root: '/');
        $nullRootDisk  = new Disk('local', 'diskC', root: null);

        // WHEN
        $rootA = $emptyRootDisk->root;
        $rootB = $slashRootDisk->root;
        $rootC = $nullRootDisk->root;

        // THEN
        expect($rootA)->toBeNull();
        expect($rootB)->toBeNull();
        expect($rootC)->toBeNull();
    });

    it('checks allowed extensions and falls back to upload config when disk has none', function (): void {
        // GIVEN
        $uploadConfig = new UploadConfig(
            chunkMinSize: 1024,
            chunkMaxSize: 1048576,
            tempDisk: null,
            tempPath: 'temp',
            duplicatePolicy: DuplicatePolicy::OVERWRITE,
            sanitizeFilenames: true,
            allowedExtensions: ['jpg', 'png', 'pdf'],
        );
        $restrictedDisk   = new Disk('local', 'restricted', allowedExtensions: ['jpg', 'png']);
        $unrestrictedDisk = new Disk('local', 'unrestricted');

        // WHEN
        $pdfOnRestricted   = $restrictedDisk->isExtensionAllowed('pdf', $uploadConfig);
        $jpgOnRestricted   = $restrictedDisk->isExtensionAllowed('jpg', $uploadConfig);
        $phpOnRestricted   = $restrictedDisk->isExtensionAllowed('php', $uploadConfig);
        $pdfOnUnrestricted = $unrestrictedDisk->isExtensionAllowed('pdf', $uploadConfig);
        $zipOnUnrestricted = $unrestrictedDisk->isExtensionAllowed('zip', $uploadConfig);

        // THEN
        expect($pdfOnRestricted)->toBeFalse();
        expect($jpgOnRestricted)->toBeTrue();
        expect($phpOnRestricted)->toBeFalse();
        expect($pdfOnUnrestricted)->toBeTrue();
        expect($zipOnUnrestricted)->toBeFalse();
    });

    it('throws when trash retention days is negative', function (): void {
        // GIVEN
        $action = fn (): Disk => new Disk(
            driver: 'local',
            name: 'local',
            trashRetentionDays: -1,
        );

        // WHEN / THEN
        expect($action)->toThrow(\InvalidArgumentException::class, 'Trash retention days must be non-negative.');
    });

    it('throws when max file size is not positive', function (): void {
        // GIVEN
        $action = fn (): Disk => new Disk(
            driver: 'local',
            name: 'local',
            maxFileSize: 0,
        );

        // WHEN / THEN
        expect($action)->toThrow(\InvalidArgumentException::class, 'Max file size must be greater than zero.');
    });

    it('throws when quota is not positive', function (): void {
        // GIVEN
        $action = fn (): Disk => new Disk(
            driver: 'local',
            name: 'local',
            quota: -100,
        );

        // WHEN / THEN
        expect($action)->toThrow(\InvalidArgumentException::class, 'Quota must be greater than zero.');
    });

    it('throws a DomainException when driver is empty', function (): void {
        // GIVEN
        $emptyDriver = '';
        $name        = 'my-disk';
        $throw       = true;
        $report      = false;

        // WHEN
        $action = fn (): Disk => new Disk($emptyDriver, $name, $throw, $report);

        // THEN
        expect($action)
            ->toThrow(IllegalDomainStateException::class, 'Disk driver cannot be empty.');
    });

    it('throws a DomainException when name is empty', function (): void {
        // GIVEN
        $emptyName = '';
        $driver    = 'local';
        $throw     = true;
        $report    = false;

        // WHEN
        $action = fn (): Disk => new Disk($driver, $emptyName, $throw, $report);

        // THEN
        expect($action)
            ->toThrow(IllegalDomainStateException::class, 'Disk name cannot be empty.');
    });

    it('assertWritable does nothing on a writable disk', function (): void {
        // GIVEN
        $disk = new Disk('local', 'my-disk', false, false, false);

        // WHEN / THEN
        $disk->assertWritable();
        expect(true)->toBeTrue();
    });

    it('assertWritable throws FileOperationException on a read-only disk', function (): void {
        // GIVEN
        $disk = new Disk('local', 'my-disk', false, false, true);

        // WHEN
        $action = static function () use ($disk): void {
            $disk->assertWritable();
        };

        // THEN
        expect($action)->toThrow(
            FileOperationException::class
        );
    });
})->covers(Disk::class);
