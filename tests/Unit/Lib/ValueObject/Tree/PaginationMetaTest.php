<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PaginationMeta;

describe('PaginationMeta', function (): void {
    it('calculates total pages and hasMore correctly for multiple pages', function (): void {
        // GIVEN
        $currentPage      = 1;
        $perPage          = 50;
        $totalFiles       = 125;
        $totalDirectories = 4;

        // WHEN
        $meta = new PaginationMeta($currentPage, $perPage, $totalFiles, $totalDirectories);

        // THEN
        expect($meta->currentPage)->toBe(1);
        expect($meta->perPage)->toBe(50);
        expect($meta->totalFiles)->toBe(125);
        expect($meta->totalDirectories)->toBe(4);
        expect($meta->totalPages)->toBe(3);
        expect($meta->hasMore)->toBeTrue();
    });

    it('sets hasMore to false on the last page', function (): void {
        // GIVEN
        $currentPage = 3;
        $perPage     = 50;
        $totalFiles  = 125;

        // WHEN
        $meta = new PaginationMeta($currentPage, $perPage, $totalFiles);

        // THEN
        expect($meta->totalPages)->toBe(3);
        expect($meta->hasMore)->toBeFalse();
    });

    it('handles empty listings with zero total files', function (): void {
        // GIVEN
        $currentPage = 1;
        $perPage     = 50;
        $totalFiles  = 0;

        // WHEN
        $meta = new PaginationMeta($currentPage, $perPage, $totalFiles);

        // THEN
        expect($meta->totalPages)->toBe(0);
        expect($meta->hasMore)->toBeFalse();
    });

    it('throws exception if current page is less than 1', function (): void {
        // GIVEN
        $currentPage = 0;
        $perPage     = 50;
        $totalFiles  = 10;

        // WHEN
        $action = fn (): PaginationMeta => new PaginationMeta($currentPage, $perPage, $totalFiles);

        // THEN
        expect($action)->toThrow(IllegalDomainStateException::class, 'Current page must be at least 1.');
    });

    it('throws exception if per page is less than 1', function (): void {
        // GIVEN
        $currentPage = 1;
        $perPage     = 0;
        $totalFiles  = 10;

        // WHEN
        $action = fn (): PaginationMeta => new PaginationMeta($currentPage, $perPage, $totalFiles);

        // THEN
        expect($action)->toThrow(IllegalDomainStateException::class, 'Per page must be at least 1.');
    });

    it('throws exception if total files is negative', function (): void {
        // GIVEN
        $currentPage = 1;
        $perPage     = 50;
        $totalFiles  = -1;

        // WHEN
        $action = fn (): PaginationMeta => new PaginationMeta($currentPage, $perPage, $totalFiles);

        // THEN
        expect($action)->toThrow(IllegalDomainStateException::class, 'Total files must be non-negative.');
    });

    it('throws exception if total directories is negative', function (): void {
        // GIVEN
        $currentPage      = 1;
        $perPage          = 50;
        $totalFiles       = 10;
        $totalDirectories = -1;

        // WHEN
        $action = fn (): PaginationMeta => new PaginationMeta($currentPage, $perPage, $totalFiles, $totalDirectories);

        // THEN
        expect($action)->toThrow(IllegalDomainStateException::class, 'Total directories must be non-negative.');
    });
})->covers(PaginationMeta::class);
