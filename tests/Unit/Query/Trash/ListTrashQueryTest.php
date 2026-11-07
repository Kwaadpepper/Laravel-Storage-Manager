<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Trash\TrashedItem;
use Kwaadpepper\LaravelStorageManager\Query\Trash\ListTrashInput;
use Kwaadpepper\LaravelStorageManager\Query\Trash\ListTrashQuery;
use Kwaadpepper\LaravelStorageManager\Service\TrashService;

describe('ListTrashQuery', function (): void {
    it('returns trashed items for a disk', function (): void {
        // GIVEN
        $trashService = \Mockery::mock(TrashService::class);
        $disk         = new Disk('local', 'local');
        $item         = new TrashedItem(
            trashId: 't1',
            originalPath: new Path('/file.txt'),
            name: 'file.txt',
            type: 'file',
            size: 50,
            deletedAt: CarbonImmutable::now(),
            diskName: 'local'
        );

        $trashService->shouldReceive('list')->with($disk)->once()->andReturn([$item]);

        $query = new ListTrashQuery($trashService);

        // WHEN
        $result = $query(new ListTrashInput($disk));

        // THEN
        expect($result)->toHaveCount(1);
        expect($result[0]->trashId)->toBe('t1');
    });
})->covers(ListTrashQuery::class);
