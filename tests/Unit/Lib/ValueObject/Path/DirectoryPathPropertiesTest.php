<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\DirectoryPathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;

describe('DirectoryPathProperties', function (): void {
    it('stores all constructor arguments as readonly properties', function (): void {
        // GIVEN
        $path       = new Path('/documents');
        $basename   = 'documents';
        $dirname    = '/';
        $timestamp  = new \DateTimeImmutable('2024-01-15 10:00:00');
        $visibility = PathVisibility::PUBLIC;

        // WHEN
        $props = new DirectoryPathProperties($path, $basename, $dirname, $timestamp, $visibility);

        // THEN
        expect($props->path)->toBe($path);
        expect($props->basename)->toBe($basename);
        expect($props->dirname)->toBe($dirname);
        expect($props->timestamp)->toBe($timestamp);
        expect($props->visibility)->toBe($visibility);
    });

    it('accepts private visibility', function (): void {
        // GIVEN
        $path       = new Path('/private');
        $basename   = 'private';
        $dirname    = '/';
        $timestamp  = new \DateTimeImmutable();
        $visibility = PathVisibility::PRIVATE;

        // WHEN
        $props = new DirectoryPathProperties($path, $basename, $dirname, $timestamp, $visibility);

        // THEN
        expect($props->visibility)->toBe(PathVisibility::PRIVATE);
    });
});
