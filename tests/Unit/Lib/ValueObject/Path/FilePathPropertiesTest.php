<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;

describe('FilePathProperties', function (): void {
    it('stores all constructor arguments as readonly properties', function (): void {
        // GIVEN
        $path       = new Path('/uploads/photo.jpg');
        $basename   = 'photo.jpg';
        $dirname    = '/uploads';
        $extension  = 'jpg';
        $filename   = 'photo';
        $size       = 204800;
        $timestamp  = new \DateTimeImmutable('2024-06-01 08:30:00');
        $visibility = PathVisibility::PUBLIC;

        // WHEN
        $props = new FilePathProperties($path, $basename, $dirname, $extension, $filename, $size, $timestamp, $visibility);

        // THEN
        expect($props->path)->toBe($path);
        expect($props->basename)->toBe($basename);
        expect($props->dirname)->toBe($dirname);
        expect($props->extension)->toBe($extension);
        expect($props->filename)->toBe($filename);
        expect($props->size)->toBe($size);
        expect($props->timestamp)->toBe($timestamp);
        expect($props->visibility)->toBe($visibility);
    });

    it('accepts a zero-byte file size', function (): void {
        // GIVEN
        $path       = new Path('/empty.txt');
        $basename   = 'empty.txt';
        $dirname    = '/';
        $extension  = 'txt';
        $filename   = 'empty';
        $size       = 0;
        $timestamp  = new \DateTimeImmutable();
        $visibility = PathVisibility::PRIVATE;

        // WHEN
        $props = new FilePathProperties($path, $basename, $dirname, $extension, $filename, $size, $timestamp, $visibility);

        // THEN
        expect($props->size)->toBe(0);
    });

    it('accepts private visibility', function (): void {
        // GIVEN
        $path       = new Path('/secret.pdf');
        $basename   = 'secret.pdf';
        $dirname    = '/';
        $extension  = 'pdf';
        $filename   = 'secret';
        $size       = 1024;
        $timestamp  = new \DateTimeImmutable();
        $visibility = PathVisibility::PRIVATE;

        // WHEN
        $props = new FilePathProperties($path, $basename, $dirname, $extension, $filename, $size, $timestamp, $visibility);

        // THEN
        expect($props->visibility)->toBe(PathVisibility::PRIVATE);
    });
});
