<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;

describe('FilePathProperties', function (): void {
    it('stores all constructor arguments as readonly properties', function (): void {
        // Given
        $path       = new Path('/uploads/photo.jpg');
        $basename   = 'photo.jpg';
        $dirname    = '/uploads';
        $extension  = 'jpg';
        $filename   = 'photo';
        $size       = 204800;
        $timestamp  = new \DateTimeImmutable('2024-06-01 08:30:00');
        $visibility = PathVisibility::PUBLIC;

        // When
        $props = new FilePathProperties($path, $basename, $dirname, $extension, $filename, $size, $timestamp, $visibility);

        // Then
        expect($props->path)->toBe($path)
            ->and($props->basename)->toBe($basename)
            ->and($props->dirname)->toBe($dirname)
            ->and($props->extension)->toBe($extension)
            ->and($props->filename)->toBe($filename)
            ->and($props->size)->toBe($size)
            ->and($props->timestamp)->toBe($timestamp)
            ->and($props->visibility)->toBe($visibility);
    });

    it('accepts a zero-byte file size', function (): void {
        // Given / When
        $props = new FilePathProperties(
            new Path('/empty.txt'),
            'empty.txt',
            '/',
            'txt',
            'empty',
            0,
            new \DateTimeImmutable(),
            PathVisibility::PRIVATE
        );

        // Then
        expect($props->size)->toBe(0);
    });

    it('accepts private visibility', function (): void {
        // Given / When
        $props = new FilePathProperties(
            new Path('/secret.pdf'),
            'secret.pdf',
            '/',
            'pdf',
            'secret',
            1024,
            new \DateTimeImmutable(),
            PathVisibility::PRIVATE
        );

        // Then
        expect($props->visibility)->toBe(PathVisibility::PRIVATE);
    });
});
