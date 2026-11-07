<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;

describe('PathTreeDirectory', function (): void {
    it('stores path and hasSubDirectories when path starts with a slash', function (): void {
        // GIVEN
        $pathString        = '/documents/reports';
        $path              = new Path($pathString);
        $hasSubDirectories = true;

        // WHEN
        $dir = new PathTreeDirectory($path, $hasSubDirectories, PathVisibility::PUBLIC);

        // THEN
        expect($dir->path)->toBe($path);
        expect($dir->hasSubDirectories)->toBeTrue();
    });

    it('accepts a path with no sub-directories', function (): void {
        // GIVEN
        $path = new Path('/images');

        // WHEN
        $dir = new PathTreeDirectory($path, false, PathVisibility::PUBLIC);

        // THEN
        expect($dir->hasSubDirectories)->toBeFalse();
    });
});
