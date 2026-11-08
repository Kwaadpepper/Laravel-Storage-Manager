<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PaginatedDirectoryContent;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PaginationMeta;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeFile;

describe('PaginatedDirectoryContent', function (): void {
    it('stores a list of PathTreeDirectory and PathTreeFile instances with pagination', function (): void {
        // GIVEN
        $directories = [
            new PathTreeDirectory(new Path('/images'), false, PathVisibility::PUBLIC),
        ];
        $files = [
            new PathTreeFile(new Path('/images/photo.jpg'), 2048, 'jpg', PathVisibility::PUBLIC, 'http://localhost/photo.jpg'),
        ];
        $pagination = new PaginationMeta(1, 50, 1, 1);

        // WHEN
        $content = new PaginatedDirectoryContent($directories, $files, $pagination);

        // THEN
        expect($content->directories)->toBe($directories);
        expect($content->files)->toBe($files);
        expect($content->pagination)->toBe($pagination);
    });

    it('accepts empty directories and files list', function (): void {
        // GIVEN
        $directories = [];
        $files       = [];
        $pagination  = new PaginationMeta(1, 50, 0, 0);

        // WHEN
        $content = new PaginatedDirectoryContent($directories, $files, $pagination);

        // THEN
        expect($content->directories)->toBeEmpty();
        expect($content->files)->toBeEmpty();
        expect($content->pagination->totalFiles)->toBe(0);
    });

    it('throws IllegalDomainStateException when directories contains an invalid item', function (): void {
        // GIVEN
        $invalidDirectories = [new PathTreeDirectory(new Path('/valid'), false, null), 'not-a-directory'];
        $files              = [];
        $pagination         = new PaginationMeta(1, 50, 0);

        // WHEN
        $action = fn (): PaginatedDirectoryContent => new PaginatedDirectoryContent($invalidDirectories, $files, $pagination);

        // THEN
        expect($action)
            ->toThrow(IllegalDomainStateException::class, 'All items in directories must be instances of PathTreeDirectory.');
    });

    it('throws IllegalDomainStateException when files contains an invalid item', function (): void {
        // GIVEN
        $directories  = [];
        $invalidFiles = ['not-a-file'];
        $pagination   = new PaginationMeta(1, 50, 0);

        // WHEN
        $action = fn (): PaginatedDirectoryContent => new PaginatedDirectoryContent($directories, $invalidFiles, $pagination);

        // THEN
        expect($action)
            ->toThrow(IllegalDomainStateException::class, 'All items in files must be instances of PathTreeFile.');
    });
})->covers(PaginatedDirectoryContent::class);
