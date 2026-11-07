<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileSearchService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeFile;

describe('FileSearchService', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
        Config::set('storage-manager.trash.enabled', true);
        Config::set('storage-manager.trash.folder', '.trash');
    });

    it('searches files by partial name case-insensitively', function (): void {
        // GIVEN
        Storage::disk('local')->put('documents/report_2026.pdf', 'report content');
        Storage::disk('local')->put('documents/invoice.pdf', 'invoice content');
        Storage::disk('local')->put('documents/REPORT_FINAL.txt', 'final report');
        $disk          = new Disk('local', 'local');
        $searchService = resolve(FileSearchService::class);

        // WHEN
        $result = $searchService->search(
            $disk,
            [new Path('/documents')],
            'report',
            null,
            null,
            null
        );

        // THEN
        expect($result->matchedFiles)->toHaveCount(2);
        $filePaths = array_map(fn (PathTreeFile $f): string => $f->path->value, $result->matchedFiles);
        expect($filePaths)->toContain('/documents/report_2026.pdf');
        expect($filePaths)->toContain('/documents/REPORT_FINAL.txt');
        expect($filePaths)->not->toContain('/documents/invoice.pdf');
    });

    it('filters search results by extension case-insensitively', function (): void {
        // GIVEN
        Storage::disk('local')->put('files/doc1.PDF', 'pdf content');
        Storage::disk('local')->put('files/doc2.pdf', 'pdf content 2');
        Storage::disk('local')->put('files/doc3.txt', 'text content');
        $disk          = new Disk('local', 'local');
        $searchService = resolve(FileSearchService::class);

        // WHEN
        $result = $searchService->search(
            $disk,
            [new Path('/files')],
            null,
            'pdf',
            null,
            null
        );

        // THEN
        expect($result->matchedFiles)->toHaveCount(2);
        $filePaths = array_map(fn (PathTreeFile $f): string => $f->path->value, $result->matchedFiles);
        expect($filePaths)->toContain('/files/doc1.PDF');
        expect($filePaths)->toContain('/files/doc2.pdf');
        expect($filePaths)->not->toContain('/files/doc3.txt');
    });

    it('filters search results by minSize and maxSize', function (): void {
        // GIVEN
        Storage::disk('local')->put('sizes/small.txt', str_repeat('a', 50));
        Storage::disk('local')->put('sizes/medium.txt', str_repeat('b', 500));
        Storage::disk('local')->put('sizes/large.txt', str_repeat('c', 5000));
        $disk          = new Disk('local', 'local');
        $searchService = resolve(FileSearchService::class);

        // WHEN
        $result = $searchService->search(
            $disk,
            [new Path('/sizes')],
            null,
            null,
            100,
            1000
        );

        // THEN
        expect($result->matchedFiles)->toHaveCount(1);
        expect($result->matchedFiles[0]->path->value)->toBe('/sizes/medium.txt');
    });

    it('excludes trash directory from search scan', function (): void {
        // GIVEN
        Storage::disk('local')->makeDirectory('.trash');
        Storage::disk('local')->put('.trash/deleted.txt', 'trashed file');
        Storage::disk('local')->put('active.txt', 'active file');
        $disk          = new Disk('local', 'local');
        $searchService = resolve(FileSearchService::class);

        // WHEN
        $result = $searchService->search(
            $disk,
            [new Path('/.trash'), new Path('/')],
            null,
            null,
            null,
            null
        );

        // THEN
        $filePaths = array_map(fn (PathTreeFile $f): string => $f->path->value, $result->matchedFiles);
        expect($filePaths)->toContain('/active.txt');
        expect($filePaths)->not->toContain('/.trash/deleted.txt');
    });

    it('matches directory names when query matches and no file-specific filters are applied', function (): void {
        // GIVEN
        Storage::disk('local')->makeDirectory('projects');
        Storage::disk('local')->makeDirectory('projects/project_alpha');
        Storage::disk('local')->makeDirectory('projects/other_folder');
        $disk          = new Disk('local', 'local');
        $searchService = resolve(FileSearchService::class);

        // WHEN
        $result = $searchService->search(
            $disk,
            [new Path('/projects')],
            'alpha',
            null,
            null,
            null
        );

        // THEN
        expect($result->matchedDirectories)->toHaveCount(1);
        expect($result->matchedDirectories[0]->path->value)->toBe('/projects/project_alpha');
    });

    it('does not match directories when file-specific filters such as extension are present', function (): void {
        // GIVEN
        Storage::disk('local')->makeDirectory('projects/alpha');
        Storage::disk('local')->put('projects/alpha.pdf', 'content');
        $disk          = new Disk('local', 'local');
        $searchService = resolve(FileSearchService::class);

        // WHEN
        $result = $searchService->search(
            $disk,
            [new Path('/projects')],
            'alpha',
            'pdf',
            null,
            null
        );

        // THEN
        expect($result->matchedDirectories)->toBeEmpty();
        expect($result->matchedFiles)->toHaveCount(1);
        expect($result->matchedFiles[0]->path->value)->toBe('/projects/alpha.pdf');
    });

    it('deduplicates identical input paths and handles leading dot in extension', function (): void {
        // GIVEN
        Storage::disk('local')->put('docs/doc1.pdf', 'content');
        $disk          = new Disk('local', 'local');
        $searchService = resolve(FileSearchService::class);

        // WHEN
        $result = $searchService->search(
            $disk,
            [new Path('/docs'), new Path('/docs/'), new Path('/docs')],
            null,
            '.pdf',
            null,
            null
        );

        // THEN
        expect($result->matchedFiles)->toHaveCount(1);
        expect($result->matchedFiles[0]->path->value)->toBe('/docs/doc1.pdf');
    });
})->covers(FileSearchService::class);
