<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeFile;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeLevel;

describe('PathTreeLevel', function (): void {
    it('stores a list of PathTreeDirectory instances', function (): void {
        // GIVEN
        $directories = [
            new PathTreeDirectory(new Path('/images'), false, PathVisibility::PUBLIC),
            new PathTreeDirectory(new Path('/documents'), true, PathVisibility::PRIVATE),
        ];
        $files = [
            new PathTreeFile(new Path('/documents/report.pdf'), 1024, 'pdf', PathVisibility::PUBLIC),
        ];

        // WHEN
        $level = new PathTreeLevel($directories, $files);

        // THEN
        expect($level->directories)->toBe($directories);
    });

    it('accepts an empty directories list', function (): void {
        // GIVEN
        $directories = [];
        $files       = [];

        // WHEN
        $level = new PathTreeLevel($directories, $files);

        // THEN
        expect($level->directories)->toBeEmpty();
    });

    it('throws a DomainException when the list contains a non-PathTreeDirectory item', function (): void {
        // GIVEN
        $invalidList = [new PathTreeDirectory(new Path('/valid'), false, PathVisibility::PUBLIC), 'not-a-directory'];
        $files       = [];

        // WHEN
        $action = fn (): PathTreeLevel => new PathTreeLevel($invalidList, $files);

        // THEN
        expect($action)
            ->toThrow(IllegalDomainStateException::class, 'All items in directories must be instances of PathTreeDirectory.');
    });
});
