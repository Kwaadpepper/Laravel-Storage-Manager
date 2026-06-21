<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;

describe('PathTreeDirectory', function (): void {
    it('stores path and hasSubDirectories when path starts with a slash', function (): void {
        // Given
        $pathString        = '/documents/reports';
        $path              = new Path($pathString);
        $hasSubDirectories = true;

        // When
        $dir = new PathTreeDirectory($path, $hasSubDirectories, PathVisibility::PUBLIC);

        // Then
        expect($dir->path)->toBe($path)
            ;
        expect($dir->hasSubDirectories)->toBeTrue();
    });

    it('accepts a path with no sub-directories', function (): void {
        // Given
        $path = new Path('/images');

        // When
        $dir = new PathTreeDirectory($path, false, PathVisibility::PUBLIC);

        // Then
        expect($dir->hasSubDirectories)->toBeFalse();
    });
});
