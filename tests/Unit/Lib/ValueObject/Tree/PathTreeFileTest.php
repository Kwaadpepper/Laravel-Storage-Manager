<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeFile;

describe('PathTreeFile Value Object', function (): void {
    it('creates a valid PathTreeFile with visibility and url', function (): void {
        // Given
        $path       = new Path('/docs/readme.txt');
        $size       = 1024;
        $extension  = 'txt';
        $visibility = PathVisibility::PUBLIC;
        $publicUrl  = 'https://example.com/docs/readme.txt';

        // When
        $file = new PathTreeFile($path, $size, $extension, $visibility, $publicUrl);

        // Then
        expect($file->path->value)->toBe('/docs/readme.txt');
        expect($file->size)->toBe(1024);
        expect($file->extension)->toBe('txt');
        expect($file->visibility)->toBe(PathVisibility::PUBLIC);
        expect($file->publicUrl)->toBe('https://example.com/docs/readme.txt');
    });

    it('creates a valid PathTreeFile with null visibility and url', function (): void {
        // Given
        $path      = new Path('/secret/data.bin');
        $size      = 2048;
        $extension = 'bin';

        // When
        $file = new PathTreeFile($path, $size, $extension, null, null);

        // Then
        expect($file->path->value)->toBe('/secret/data.bin');
        expect($file->size)->toBe(2048);
        expect($file->extension)->toBe('bin');
        expect($file->visibility)->toBeNull();
        expect($file->publicUrl)->toBeNull();
    });
});
