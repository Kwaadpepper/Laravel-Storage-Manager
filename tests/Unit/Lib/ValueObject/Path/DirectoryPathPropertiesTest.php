<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\DirectoryPathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;

describe('DirectoryPathProperties', function (): void {
    it('stores all constructor arguments as readonly properties', function (): void {
        // Given
        $path       = new Path('/documents');
        $basename   = 'documents';
        $dirname    = '/';
        $timestamp  = new \DateTimeImmutable('2024-01-15 10:00:00');
        $visibility = PathVisibility::PUBLIC;

        // When
        $props = new DirectoryPathProperties($path, $basename, $dirname, $timestamp, $visibility);

        // Then
        expect($props->path)->toBe($path)
            ->and($props->basename)->toBe($basename)
            ->and($props->dirname)->toBe($dirname)
            ->and($props->timestamp)->toBe($timestamp)
            ->and($props->visibility)->toBe($visibility);
    });

    it('accepts private visibility', function (): void {
        // Given
        $props = new DirectoryPathProperties(
            new Path('/private'),
            'private',
            '/',
            new \DateTimeImmutable(),
            PathVisibility::PRIVATE
        );

        // When / Then
        expect($props->visibility)->toBe(PathVisibility::PRIVATE);
    });
});
