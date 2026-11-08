<?php

declare(strict_types=1);

namespace Tests\Unit\Event;

use Kwaadpepper\LaravelStorageManager\Event\ArchiveExtracting;
use Kwaadpepper\LaravelStorageManager\Event\BulkRenaming;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryCopying;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryCreating;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryDeleting;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryMoving;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryRenaming;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryRestoring;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryTrashing;
use Kwaadpepper\LaravelStorageManager\Event\FileCopying;
use Kwaadpepper\LaravelStorageManager\Event\FileCreating;
use Kwaadpepper\LaravelStorageManager\Event\FileDeleting;
use Kwaadpepper\LaravelStorageManager\Event\FileMoving;
use Kwaadpepper\LaravelStorageManager\Event\FileRenaming;
use Kwaadpepper\LaravelStorageManager\Event\FileRestoring;
use Kwaadpepper\LaravelStorageManager\Event\FileTrashing;
use Kwaadpepper\LaravelStorageManager\Event\TrashEmptying;
use Kwaadpepper\LaravelStorageManager\Event\ZipCreating;

describe('BeforeEvents', function (): void {
    it('instantiates FileCreating via make', function (): void {
        // GIVEN
        $params = ['disk' => 'local', 'path' => '/file.txt', 'size' => 100];

        // WHEN
        $event = FileCreating::make(null, $params);

        // THEN
        expect($event->disk)->toBe('local');
        expect($event->path)->toBe('/file.txt');
        expect($event->size)->toBe(100);
        expect($event->user)->toBeNull();
    });

    it('instantiates DirectoryCreating via make', function (): void {
        // GIVEN
        $params = ['disk' => 'local', 'path' => '/dir'];

        // WHEN
        $event = DirectoryCreating::make(null, $params);

        // THEN
        expect($event->disk)->toBe('local');
        expect($event->path)->toBe('/dir');
        expect($event->user)->toBeNull();
    });

    it('instantiates FileDeleting and DirectoryDeleting via make', function (): void {
        // GIVEN
        $fileParams = ['disk' => 'local', 'path' => '/file.txt', 'force' => true];
        $dirParams  = ['disk' => 'local', 'path' => '/dir', 'force' => false];

        // WHEN
        $fileEvent = FileDeleting::make(null, $fileParams);
        $dirEvent  = DirectoryDeleting::make(null, $dirParams);

        // THEN
        expect($fileEvent->disk)->toBe('local');
        expect($fileEvent->path)->toBe('/file.txt');
        expect($fileEvent->force)->toBeTrue();
        expect($dirEvent->disk)->toBe('local');
        expect($dirEvent->path)->toBe('/dir');
        expect($dirEvent->force)->toBeFalse();
    });

    it('instantiates FileMoving and DirectoryMoving via make', function (): void {
        // GIVEN
        $params = ['disk' => 'local', 'source_path' => '/a.txt', 'target_path' => '/b.txt'];

        // WHEN
        $fileEvent = FileMoving::make(null, $params);
        $dirEvent  = DirectoryMoving::make(null, $params);

        // THEN
        expect($fileEvent->disk)->toBe('local');
        expect($fileEvent->sourcePath)->toBe('/a.txt');
        expect($fileEvent->targetPath)->toBe('/b.txt');
        expect($dirEvent->disk)->toBe('local');
        expect($dirEvent->sourcePath)->toBe('/a.txt');
        expect($dirEvent->targetPath)->toBe('/b.txt');
    });

    it('instantiates FileRenaming and DirectoryRenaming via make', function (): void {
        // GIVEN
        $params = ['disk' => 'local', 'old_path' => '/old.txt', 'new_path' => '/new.txt'];

        // WHEN
        $fileEvent = FileRenaming::make(null, $params);
        $dirEvent  = DirectoryRenaming::make(null, $params);

        // THEN
        expect($fileEvent->disk)->toBe('local');
        expect($fileEvent->oldPath)->toBe('/old.txt');
        expect($fileEvent->newPath)->toBe('/new.txt');
        expect($dirEvent->disk)->toBe('local');
        expect($dirEvent->oldPath)->toBe('/old.txt');
        expect($dirEvent->newPath)->toBe('/new.txt');
    });

    it('instantiates FileCopying and DirectoryCopying via make', function (): void {
        // GIVEN
        $params = ['disk' => 'local', 'source_path' => '/src.txt', 'target_path' => '/dst.txt'];

        // WHEN
        $fileEvent = FileCopying::make(null, $params);
        $dirEvent  = DirectoryCopying::make(null, $params);

        // THEN
        expect($fileEvent->disk)->toBe('local');
        expect($fileEvent->sourcePath)->toBe('/src.txt');
        expect($fileEvent->targetPath)->toBe('/dst.txt');
        expect($dirEvent->disk)->toBe('local');
        expect($dirEvent->sourcePath)->toBe('/src.txt');
        expect($dirEvent->targetPath)->toBe('/dst.txt');
    });

    it('instantiates FileTrashing and DirectoryTrashing via make', function (): void {
        // GIVEN
        $params = ['disk' => 'local', 'path' => '/trash_me'];

        // WHEN
        $fileEvent = FileTrashing::make(null, $params);
        $dirEvent  = DirectoryTrashing::make(null, $params);

        // THEN
        expect($fileEvent->disk)->toBe('local');
        expect($fileEvent->path)->toBe('/trash_me');
        expect($dirEvent->disk)->toBe('local');
        expect($dirEvent->path)->toBe('/trash_me');
    });

    it('instantiates FileRestoring and DirectoryRestoring via make', function (): void {
        // GIVEN
        $params = ['disk' => 'local', 'path' => '/restore_me', 'trashId' => 'uuid-123'];

        // WHEN
        $fileEvent = FileRestoring::make(null, $params);
        $dirEvent  = DirectoryRestoring::make(null, $params);

        // THEN
        expect($fileEvent->disk)->toBe('local');
        expect($fileEvent->path)->toBe('/restore_me');
        expect($fileEvent->trashId)->toBe('uuid-123');
        expect($dirEvent->disk)->toBe('local');
        expect($dirEvent->path)->toBe('/restore_me');
        expect($dirEvent->trashId)->toBe('uuid-123');
    });

    it('instantiates BulkRenaming via make', function (): void {
        // GIVEN
        $renames = [['from' => '/a.txt', 'to' => '/b.txt']];
        $params  = ['disk' => 'local', 'renames' => $renames];

        // WHEN
        $event = BulkRenaming::make(null, $params);

        // THEN
        expect($event->disk)->toBe('local');
        expect($event->renames)->toBe($renames);
    });

    it('instantiates ZipCreating and ArchiveExtracting via make', function (): void {
        // GIVEN
        $zipParams    = ['disk' => 'local', 'paths' => ['/a.txt'], 'destination' => '/archive.zip'];
        $unzipParams  = ['disk' => 'local', 'archive' => '/archive.zip', 'destination' => '/extracted'];

        // WHEN
        $zipEvent   = ZipCreating::make(null, $zipParams);
        $unzipEvent = ArchiveExtracting::make(null, $unzipParams);

        // THEN
        expect($zipEvent->disk)->toBe('local');
        expect($zipEvent->paths)->toBe(['/a.txt']);
        expect($zipEvent->destination)->toBe('/archive.zip');
        expect($unzipEvent->disk)->toBe('local');
        expect($unzipEvent->archive)->toBe('/archive.zip');
        expect($unzipEvent->destination)->toBe('/extracted');
    });

    it('instantiates TrashEmptying via make', function (): void {
        // GIVEN
        $params = ['disk' => 'local', 'count' => 5];

        // WHEN
        $event = TrashEmptying::make(null, $params);

        // THEN
        expect($event->disk)->toBe('local');
        expect($event->count)->toBe(5);
    });
})->covers(
    FileCreating::class,
    DirectoryCreating::class,
    FileDeleting::class,
    DirectoryDeleting::class,
    FileMoving::class,
    DirectoryMoving::class,
    FileRenaming::class,
    DirectoryRenaming::class,
    FileCopying::class,
    DirectoryCopying::class,
    FileTrashing::class,
    DirectoryTrashing::class,
    FileRestoring::class,
    DirectoryRestoring::class,
    BulkRenaming::class,
    ZipCreating::class,
    ArchiveExtracting::class,
    TrashEmptying::class,
);
