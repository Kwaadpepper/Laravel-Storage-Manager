<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Kwaadpepper\LaravelStorageManager\ActivityLog\ActivityLogger;
use Kwaadpepper\LaravelStorageManager\ActivityLog\Event\ActivityLogged;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Psr\Log\LoggerInterface;

describe('ActivityLogger', function (): void {
    it('records activity and dispatches ActivityLogged event', function (): void {
        // GIVEN
        $eventDispatcher = \Mockery::mock(EventDispatcher::class);
        $logger          = \Mockery::mock(LoggerInterface::class);
        $activityLogger  = new ActivityLogger($eventDispatcher, $logger);

        $user  = \Mockery::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn('user-42');

        $disk  = new Disk('local', 'local');
        $path  = new Path('/folder/file.txt');
        $input = new class ($disk, $path) implements ActionInput
        {
            public function __construct(
                private readonly Disk $disk,
                private readonly Path $path,
            ) {
            }

            public function getDisk(): Disk
            {
                return $this->disk;
            }

            public function getPath(): Path
            {
                return $this->path;
            }
        };

        $action = new class () implements Action
        {
            public function ability(): string
            {
                return 'storage-manager.delete';
            }

            public function __invoke(ActionInput $input): bool
            {
                return true;
            }
        };

        $logger->shouldReceive('info')->once();
        $eventDispatcher->shouldReceive('dispatch')->with(
            ActivityLogged::class,
            \Mockery::on(function (array $payload): bool {
                return $payload['disk'] === 'local'
                    && $payload['path'] === '/folder/file.txt';
            })
        )->once();

        // WHEN
        $activityLogger->record($user, $action, $input, true);

        // THEN
        expect(true)->toBeTrue();
    });
})->covers(ActivityLogger::class);
