<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Http\Dto\Disk\DiskStatsDto;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Query\Disk\GetConfiguredDisksInput;
use Kwaadpepper\LaravelStorageManager\Query\Disk\GetConfiguredDisksQuery;
use Kwaadpepper\LaravelStorageManager\Query\Disk\GetDiskStatsInput;
use Kwaadpepper\LaravelStorageManager\Query\Disk\GetDiskStatsQuery;
use Kwaadpepper\LaravelStorageManager\Query\Disk\SelectDiskInput;
use Kwaadpepper\LaravelStorageManager\Query\Disk\SelectDiskQuery;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;

describe('DiskQueries', function (): void {
    it('returns configured disks list via GetConfiguredDisksQuery', function (): void {
        // GIVEN
        $diskService = \Mockery::mock(DiskService::class);
        $diskService->shouldReceive('getDiskNamesList')->once()->andReturn(['local', 's3']);
        $query = new GetConfiguredDisksQuery($diskService);

        // WHEN
        $result = $query(new GetConfiguredDisksInput());

        // THEN
        expect($result)->toBe(['local', 's3']);
    });

    it('selects a disk via SelectDiskQuery', function (): void {
        // GIVEN
        $diskService = \Mockery::mock(DiskService::class);
        $disk        = new Disk('local', 'local');
        $diskService->shouldReceive('selectDisk')->with($disk)->once()->andReturn($disk);
        $query = new SelectDiskQuery($diskService);

        // WHEN
        $result = $query(new SelectDiskInput($disk));

        // THEN
        expect($result->name)->toBe('local');
    });

    it('returns disk stats and clears cache when fresh via GetDiskStatsQuery', function (): void {
        // GIVEN
        $diskService = \Mockery::mock(DiskService::class);
        $disk        = new Disk('local', 'local');
        $dto         = new DiskStatsDto(
            disk: 'local',
            driver: 'local',
            supported: true,
            total: 300,
            free: 100,
            used: 200,
            percentage: 66.6,
            mode: 'exact',
        );

        $diskService->shouldReceive('clearDiskStatsCache')->with($disk)->once();
        $diskService->shouldReceive('getDiskStats')->with($disk)->once()->andReturn($dto);
        $query = new GetDiskStatsQuery($diskService);

        // WHEN
        $result = $query(new GetDiskStatsInput($disk, fresh: true));

        // THEN
        expect($result->free)->toBe(100);
        expect($result->total)->toBe(300);
    });
})->covers(GetConfiguredDisksQuery::class, SelectDiskQuery::class, GetDiskStatsQuery::class);
