<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Kwaadpepper\LaravelStorageManager\ActivityLog\ActivityLogger;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Contract\Query;
use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Service\ActionExecutorService;
use Kwaadpepper\LaravelStorageManager\Service\AuthService;
use Psr\Log\LoggerInterface;

describe('ActionExecutorService', function (): void {
    it('executes an action successfully and records activity', function (): void {
        // GIVEN
        $gate           = \Mockery::mock(Gate::class);
        $activityLogger = \Mockery::mock(ActivityLogger::class);
        $authService    = \Mockery::mock(AuthService::class);
        $logger         = \Mockery::mock(LoggerInterface::class);

        $user  = \Mockery::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn('user-1');
        $disk  = new Disk('local', 'local');
        $path  = new Path('/test.txt');
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
                return 'storage-manager.create-file';
            }

            public function __invoke(ActionInput $input): string
            {
                return 'created';
            }
        };

        $authService->shouldReceive('user')->once()->andReturn($user);
        $gate->shouldReceive('forUser')->with($user)->once()->andReturnSelf();
        $gate->shouldReceive('authorize')->with('storage-manager.create-file', [$disk, $path])->once();
        $logger->shouldReceive('info')->twice();
        $activityLogger->shouldReceive('record')->with($user, $action, $input, 'created')->once();

        $executor = new ActionExecutorService($gate, $activityLogger, $authService, $logger);

        // WHEN
        $result = $executor->execute($action, $input);

        // THEN
        expect($result)->toBe('created');
    });

    it('executes a query successfully without recording activity', function (): void {
        // GIVEN
        $gate           = \Mockery::mock(Gate::class);
        $activityLogger = \Mockery::mock(ActivityLogger::class);
        $authService    = \Mockery::mock(AuthService::class);
        $logger         = \Mockery::mock(LoggerInterface::class);

        $disk  = new Disk('local', 'local');
        $path  = new Path('/test.txt');
        $input = new class ($disk, $path) implements QueryInput
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

        $query = new class () implements Query
        {
            public function ability(): string
            {
                return 'storage-manager.view';
            }

            public function __invoke(QueryInput $input): bool
            {
                return true;
            }
        };

        $authService->shouldReceive('user')->once()->andReturn(null);
        $gate->shouldReceive('forUser')->with(null)->once()->andReturnSelf();
        $gate->shouldReceive('authorize')->with('storage-manager.view', [$disk, $path])->once();
        $logger->shouldReceive('info')->twice();
        $activityLogger->shouldNotReceive('record');

        $executor = new ActionExecutorService($gate, $activityLogger, $authService, $logger);

        // WHEN
        $result = $executor->execute($query, $input);

        // THEN
        expect($result)->toBeTrue();
    });

    it('throws InvalidArgumentException when action receives non-action input', function (): void {
        // GIVEN
        $gate           = \Mockery::mock(Gate::class);
        $activityLogger = \Mockery::mock(ActivityLogger::class);
        $authService    = \Mockery::mock(AuthService::class);
        $logger         = \Mockery::mock(LoggerInterface::class);

        $queryInput = new class () implements QueryInput
        {
            public function getDisk(): ?Disk
            {
                return null;
            }

            public function getPath(): ?Path
            {
                return null;
            }
        };

        $action = new class () implements Action
        {
            public function ability(): string
            {
                return 'storage-manager.delete';
            }

            public function __invoke(ActionInput $input): void
            {
            }
        };

        $authService->shouldReceive('user')->once()->andReturn(null);
        $executor = new ActionExecutorService($gate, $activityLogger, $authService, $logger);

        // WHEN
        $callback = fn (): mixed => $executor->execute($action, $queryInput);

        // THEN
        expect($callback)->toThrow(\InvalidArgumentException::class, 'Input must be an instance of ActionInput.');
    });

    it('throws InvalidArgumentException when query receives non-query input', function (): void {
        // GIVEN
        $gate           = \Mockery::mock(Gate::class);
        $activityLogger = \Mockery::mock(ActivityLogger::class);
        $authService    = \Mockery::mock(AuthService::class);
        $logger         = \Mockery::mock(LoggerInterface::class);

        $actionInput = new class () implements ActionInput
        {
            public function getDisk(): ?Disk
            {
                return null;
            }

            public function getPath(): ?Path
            {
                return null;
            }
        };

        $query = new class () implements Query
        {
            public function ability(): string
            {
                return 'storage-manager.view';
            }

            public function __invoke(QueryInput $input): bool
            {
                return true;
            }
        };

        $authService->shouldReceive('user')->once()->andReturn(null);
        $executor = new ActionExecutorService($gate, $activityLogger, $authService, $logger);

        // WHEN
        $callback = fn (): mixed => $executor->execute($query, $actionInput);

        // THEN
        expect($callback)->toThrow(\InvalidArgumentException::class, 'Input must be an instance of QueryInput.');
    });

    it('propagates AuthorizationException when access is denied', function (): void {
        // GIVEN
        $gate           = \Mockery::mock(Gate::class);
        $activityLogger = \Mockery::mock(ActivityLogger::class);
        $authService    = \Mockery::mock(AuthService::class);
        $logger         = \Mockery::mock(LoggerInterface::class);

        $input = new class () implements ActionInput
        {
            public function getDisk(): ?Disk
            {
                return null;
            }

            public function getPath(): ?Path
            {
                return null;
            }
        };

        $action = new class () implements Action
        {
            public function ability(): string
            {
                return 'storage-manager.delete';
            }

            public function __invoke(ActionInput $input): void
            {
            }
        };

        $authService->shouldReceive('user')->once()->andReturn(null);
        $gate->shouldReceive('forUser')->with(null)->once()->andReturnSelf();
        $gate->shouldReceive('authorize')->with('storage-manager.delete', [])->once()->andThrow(new AuthorizationException('Forbidden'));

        $executor = new ActionExecutorService($gate, $activityLogger, $authService, $logger);

        // WHEN
        $callback = fn (): mixed => $executor->execute($action, $input);

        // THEN
        expect($callback)->toThrow(AuthorizationException::class, 'Forbidden');
    });

    it('logs error and re-throws when execution fails', function (): void {
        // GIVEN
        $gate           = \Mockery::mock(Gate::class);
        $activityLogger = \Mockery::mock(ActivityLogger::class);
        $authService    = \Mockery::mock(AuthService::class);
        $logger         = \Mockery::mock(LoggerInterface::class);

        $input = new class () implements ActionInput
        {
            public function getDisk(): ?Disk
            {
                return null;
            }

            public function getPath(): ?Path
            {
                return null;
            }
        };

        $action = new class () implements Action
        {
            public function ability(): string
            {
                return 'storage-manager.delete';
            }

            public function __invoke(ActionInput $input): void
            {
                throw new \RuntimeException('Failure in action');
            }
        };

        $authService->shouldReceive('user')->once()->andReturn(null);
        $gate->shouldReceive('forUser')->with(null)->once()->andReturnSelf();
        $gate->shouldReceive('authorize')->with('storage-manager.delete', [])->once();
        $logger->shouldReceive('info')->once();
        $logger->shouldReceive('error')->once();

        $executor = new ActionExecutorService($gate, $activityLogger, $authService, $logger);

        // WHEN
        $callback = fn (): mixed => $executor->execute($action, $input);

        // THEN
        expect($callback)->toThrow(\RuntimeException::class, 'Failure in action');
    });

    it('passes disk and null path without dropping null positional argument', function (): void {
        // GIVEN
        $gate           = \Mockery::mock(Gate::class);
        $activityLogger = \Mockery::mock(ActivityLogger::class);
        $authService    = \Mockery::mock(AuthService::class);
        $logger         = \Mockery::mock(LoggerInterface::class);

        $disk  = new Disk('local', 'local');
        $input = new class ($disk) implements ActionInput
        {
            public function __construct(
                private readonly Disk $disk,
            ) {
            }

            public function getDisk(): Disk
            {
                return $this->disk;
            }

            public function getPath(): ?Path
            {
                return null;
            }
        };

        $action = new class () implements Action
        {
            public function ability(): string
            {
                return 'storage-manager.trash.empty';
            }

            public function __invoke(ActionInput $input): int
            {
                return 5;
            }
        };

        $authService->shouldReceive('user')->once()->andReturn(null);
        $gate->shouldReceive('forUser')->with(null)->once()->andReturnSelf();
        $gate->shouldReceive('authorize')->with('storage-manager.trash.empty', [$disk, null])->once();
        $logger->shouldReceive('info')->twice();
        $activityLogger->shouldReceive('record')->once();

        $executor = new ActionExecutorService($gate, $activityLogger, $authService, $logger);

        // WHEN
        $result = $executor->execute($action, $input);

        // THEN
        expect($result)->toBe(5);
    });
})->covers(ActionExecutorService::class);
