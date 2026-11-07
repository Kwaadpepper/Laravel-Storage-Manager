<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Kwaadpepper\LaravelStorageManager\Action\Trash\EmptyTrashAction;
use Kwaadpepper\LaravelStorageManager\Action\Trash\EmptyTrashInput;
use Kwaadpepper\LaravelStorageManager\Action\Trash\MoveToTrashAction;
use Kwaadpepper\LaravelStorageManager\Action\Trash\MoveToTrashInput;
use Kwaadpepper\LaravelStorageManager\Action\Trash\PurgeTrashAction;
use Kwaadpepper\LaravelStorageManager\Action\Trash\PurgeTrashInput;
use Kwaadpepper\LaravelStorageManager\Action\Trash\RestoreTrashAction;
use Kwaadpepper\LaravelStorageManager\Action\Trash\RestoreTrashInput;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Trash\TrashedItem;
use Kwaadpepper\LaravelStorageManager\Service\TrashService;

describe('TrashActions', function (): void {
    it('moves an item to trash', function (): void {
        // GIVEN
        $trashService = \Mockery::mock(TrashService::class);
        $disk         = new Disk('local', 'local');
        $path         = new Path('/doc.txt');
        $input        = new MoveToTrashInput($disk, $path);
        $trashedItem  = new TrashedItem(
            trashId: 'id-1',
            originalPath: $path,
            name: 'doc.txt',
            type: 'file',
            size: 100,
            deletedAt: CarbonImmutable::now(),
            diskName: 'local'
        );

        $trashService->shouldReceive('moveToTrash')
            ->with($disk, $path)
            ->once()
            ->andReturn($trashedItem);

        $action = new MoveToTrashAction($trashService);

        // WHEN
        $result = $action($input);

        // THEN
        expect($result->trashId)->toBe('id-1');
    });

    it('restores trashed items', function (): void {
        // GIVEN
        $trashService = \Mockery::mock(TrashService::class);
        $disk         = new Disk('local', 'local');
        $ids          = ['id-1', 'id-2'];
        $input        = new RestoreTrashInput($disk, $ids);

        $trashService->shouldReceive('restore')->with($disk, 'id-1')->once()->andReturn(new Path('/restored1.txt'));
        $trashService->shouldReceive('restore')->with($disk, 'id-2')->once()->andReturn(new Path('/restored2.txt'));

        $action = new RestoreTrashAction($trashService);

        // WHEN
        $result = $action($input);

        // THEN
        expect($result)->toBe([
            ['id' => 'id-1', 'path' => '/restored1.txt'],
            ['id' => 'id-2', 'path' => '/restored2.txt'],
        ]);
    });

    it('purges trashed items', function (): void {
        // GIVEN
        $trashService = \Mockery::mock(TrashService::class);
        $disk         = new Disk('local', 'local');
        $ids          = ['id-1', 'id-2'];
        $input        = new PurgeTrashInput($disk, $ids);

        $trashService->shouldReceive('purge')->with($disk, 'id-1')->once();
        $trashService->shouldReceive('purge')->with($disk, 'id-2')->once();

        $action = new PurgeTrashAction($trashService);

        // WHEN
        $result = $action($input);

        // THEN
        expect($result)->toBe(['id-1', 'id-2']);
    });

    it('empties trash', function (): void {
        // GIVEN
        $trashService = \Mockery::mock(TrashService::class);
        $disk         = new Disk('local', 'local');
        $input        = new EmptyTrashInput($disk);

        $trashService->shouldReceive('emptyTrash')->with($disk)->once()->andReturn(5);

        $action = new EmptyTrashAction($trashService);

        // WHEN
        $result = $action($input);

        // THEN
        expect($result)->toBe(5);
    });
})->covers(MoveToTrashAction::class, RestoreTrashAction::class, PurgeTrashAction::class, EmptyTrashAction::class);
