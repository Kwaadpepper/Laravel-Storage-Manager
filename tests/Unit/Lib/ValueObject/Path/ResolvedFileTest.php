<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\ResolvedFile;

describe('ResolvedFile', function (): void {
    it('stores all constructor arguments as readonly properties', function (): void {
        // GIVEN
        $disk       = new Disk('local', 'local');
        $path       = new Path('/docs/file.txt');
        $properties = new FilePathProperties(
            $path,
            'file.txt',
            '/docs',
            'txt',
            'file',
            128,
            new \DateTimeImmutable(),
            PathVisibility::PUBLIC
        );

        // WHEN
        $resolved = new ResolvedFile($disk, $path, $properties);

        // THEN
        expect($resolved->disk)->toBe($disk);
        expect($resolved->path)->toBe($path);
        expect($resolved->properties)->toBe($properties);
    });
});
