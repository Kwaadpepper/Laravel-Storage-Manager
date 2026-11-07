<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathList;

describe('PathList', function (): void {
    it('stores files and directories arrays', function (): void {
        // GIVEN
        $files       = [new Path('/uploads/photo.jpg')];
        $directories = [new Path('/uploads')];

        // WHEN
        $list = new PathList($files, $directories);

        // THEN
        expect($list->files)->toBe($files);
        expect($list->directories)->toBe($directories);
    });

    it('accepts empty files and directories arrays', function (): void {
        // GIVEN
        $files       = [];
        $directories = [];

        // WHEN
        $list = new PathList($files, $directories);

        // THEN
        expect($list->files)->toBeEmpty();
        expect($list->directories)->toBeEmpty();
    });

    it('throws a DomainException when files contains a non-Path item', function (): void {
        // GIVEN
        $invalidFiles = [new Path('/valid.txt'), 'not-a-path'];

        // WHEN
        $action = fn (): PathList => new PathList($invalidFiles, []);

        // THEN
        expect($action)
            ->toThrow(IllegalDomainStateException::class, 'All items in files must be instances of Path.');
    });

    it('throws a DomainException when directories contains a non-Path item', function (): void {
        // GIVEN
        $invalidDirs = [new Path('/valid'), 42];

        // WHEN
        $action = fn (): PathList => new PathList([], $invalidDirs);

        // THEN
        expect($action)
            ->toThrow(IllegalDomainStateException::class, 'All items in directories must be instances of Path.');
    });
});
