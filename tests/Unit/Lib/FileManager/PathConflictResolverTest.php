<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathConflictResolver;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

describe('PathConflictResolver', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
    });

    it('returns the same path when no collision exists', function (): void {
        // GIVEN
        $disk     = new Disk('local', 'local');
        $resolver = resolve(PathConflictResolver::class);

        // WHEN
        $result = $resolver->resolveAvailablePath($disk, new Path('/documents/report.pdf'));

        // THEN
        expect($result->value)->toBe('/documents/report.pdf');
    });

    it('resolves collision using default counter format', function (): void {
        // GIVEN
        $storage  = Storage::disk('local');
        $disk     = new Disk('local', 'local');
        $resolver = resolve(PathConflictResolver::class);
        $storage->put('documents/report.pdf', 'First report');

        // WHEN
        $firstCollision = $resolver->resolveAvailablePath($disk, new Path('/documents/report.pdf'));
        $storage->put('documents/report (1).pdf', 'Second report');
        $secondCollision = $resolver->resolveAvailablePath($disk, new Path('/documents/report.pdf'));

        // THEN
        expect($firstCollision->value)->toBe('/documents/report (1).pdf');
        expect($secondCollision->value)->toBe('/documents/report (2).pdf');
    });

    it('resolves directory collision with counter suffix', function (): void {
        // GIVEN
        $storage  = Storage::disk('local');
        $disk     = new Disk('local', 'local');
        $resolver = resolve(PathConflictResolver::class);
        $storage->makeDirectory('projects');

        // WHEN
        $result = $resolver->resolveAvailablePath($disk, new Path('/projects'));

        // THEN
        expect($result->value)->toBe('/projects (1)');
    });

    it('resolves copy collisions using custom copy format', function (): void {
        // GIVEN
        $storage  = Storage::disk('local');
        $disk     = new Disk('local', 'local');
        $resolver = resolve(PathConflictResolver::class);
        $storage->put('notes.txt', 'notes content');

        // WHEN
        $firstCopy = $resolver->resolveAvailablePath($disk, new Path('/notes.txt'), '%s (copy%s)%s');
        $storage->put('notes (copy).txt', 'first copy content');
        $secondCopy = $resolver->resolveAvailablePath($disk, new Path('/notes.txt'), '%s (copy%s)%s');

        // THEN
        expect($firstCopy->value)->toBe('/notes (copy).txt');
        expect($secondCopy->value)->toBe('/notes (copy 2).txt');
    });

    it('resolves restore collisions using custom restore format', function (): void {
        // GIVEN
        $storage  = Storage::disk('local');
        $disk     = new Disk('local', 'local');
        $resolver = resolve(PathConflictResolver::class);
        $storage->put('archive.tar', 'archive content');

        // WHEN
        $firstRestore = $resolver->resolveAvailablePath($disk, new Path('/archive.tar'), '%s (restored%s)%s');
        $storage->put('archive (restored).tar', 'first restored content');
        $secondRestore = $resolver->resolveAvailablePath($disk, new Path('/archive.tar'), '%s (restored%s)%s');

        // THEN
        expect($firstRestore->value)->toBe('/archive (restored).tar');
        expect($secondRestore->value)->toBe('/archive (restored 2).tar');
    });
})->covers(PathConflictResolver::class);
