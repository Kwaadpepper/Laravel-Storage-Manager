<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;

describe('PathTreeDirectory', function (): void {
    it('stores path and hasSubDirectories when path starts with a slash', function (): void {
        // Given
        $path              = new Path('/documents/reports');
        $hasSubDirectories = true;

        // When
        $dir = new PathTreeDirectory($path, $hasSubDirectories);

        // Then
        expect($dir->path)->toBe($path)
            ->and($dir->hasSubDirectories)->toBeTrue();
    });

    it('accepts a path with no sub-directories', function (): void {
        // Given
        $path = new Path('/images');

        // When
        $dir = new PathTreeDirectory($path, false);

        // Then
        expect($dir->hasSubDirectories)->toBeFalse();
    });
});
