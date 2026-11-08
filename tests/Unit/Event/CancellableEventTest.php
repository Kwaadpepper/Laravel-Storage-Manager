<?php

declare(strict_types=1);

namespace Tests\Unit\Event;

use Illuminate\Support\Facades\Event;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Event\FileCreating;
use Kwaadpepper\LaravelStorageManager\Exception\OperationCancelledException;
use Kwaadpepper\LaravelStorageManager\Lib\Event\CancellableTrait;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;

describe('CancellableEvent', function (): void {
    it('manages cancellation state and reason', function (): void {
        // GIVEN
        $event = new FileCreating(
            disk: 'local',
            path: '/path/to/file.txt',
            size: 1024
        );

        // WHEN
        $initialState  = $event->isCancelled();
        $initialReason = $event->cancellationReason();
        $event->cancel('Quota exceeded');
        $cancelledState  = $event->isCancelled();
        $cancelledReason = $event->cancellationReason();

        // THEN
        expect($initialState)->toBeFalse();
        expect($initialReason)->toBeNull();
        expect($cancelledState)->toBeTrue();
        expect($cancelledReason)->toBe('Quota exceeded');
    });

    it('creates OperationCancelledException with default or custom reason', function (): void {
        // GIVEN
        $defaultException = new OperationCancelledException();
        $customException  = new OperationCancelledException('User quota exceeded');

        // WHEN
        $defaultCode    = $defaultException->getDomainErrorCode();
        $defaultMessage = $defaultException->getMessage();
        $customCode     = $customException->getDomainErrorCode();
        $customMessage  = $customException->getMessage();

        // THEN
        expect($defaultCode)->toBe(FileOperationError::OPERATION_CANCELLED);
        expect($defaultMessage)->toBe(FileOperationError::OPERATION_CANCELLED->message());
        expect($customCode)->toBe(FileOperationError::OPERATION_CANCELLED);
        expect($customMessage)->toBe('User quota exceeded');
    });

    it('throws via OperationCancelledException throwWithReason helper', function (): void {
        // GIVEN
        $reason = 'File locked';

        // WHEN
        $callback = static function () use ($reason): void {
            OperationCancelledException::throwWithReason($reason);
        };

        // THEN
        expect($callback)->toThrow(OperationCancelledException::class, 'File locked');
    });

    it('dispatches cancellable event without cancellation successfully', function (): void {
        // GIVEN
        $dispatcher = resolve(EventDispatcher::class);
        $called     = false;
        Event::listen(FileCreating::class, function (FileCreating $event) use (&$called): void {
            $called = true;
        });

        // WHEN
        $dispatcher->dispatch(FileCreating::class, [
            'disk' => 'local',
            'path' => '/test.txt',
            'size' => 10,
        ]);

        // THEN
        expect($called)->toBeTrue();
    });

    it('throws OperationCancelledException when listener cancels event with reason', function (): void {
        // GIVEN
        $dispatcher = resolve(EventDispatcher::class);
        Event::listen(FileCreating::class, function (FileCreating $event): void {
            $event->cancel('Storage limit reached');
        });

        // WHEN
        $callback = static function () use ($dispatcher): void {
            $dispatcher->dispatch(FileCreating::class, [
                'disk' => 'local',
                'path' => '/test.txt',
                'size' => 10,
            ]);
        };

        // THEN
        expect($callback)->toThrow(OperationCancelledException::class, 'Storage limit reached');
    });

    it('throws OperationCancelledException with default message when cancelled without reason', function (): void {
        // GIVEN
        $dispatcher = resolve(EventDispatcher::class);
        Event::listen(FileCreating::class, function (FileCreating $event): void {
            $event->cancel();
        });

        // WHEN
        $callback = static function () use ($dispatcher): void {
            $dispatcher->dispatch(FileCreating::class, [
                'disk' => 'local',
                'path' => '/test.txt',
                'size' => 10,
            ]);
        };

        // THEN
        expect($callback)->toThrow(OperationCancelledException::class, FileOperationError::OPERATION_CANCELLED->message());
    });

    it('propagates raw exceptions thrown from listeners', function (): void {
        // GIVEN
        $dispatcher = resolve(EventDispatcher::class);
        Event::listen(FileCreating::class, function (FileCreating $event): void {
            throw new \RuntimeException('Database connection lost');
        });

        // WHEN
        $callback = static function () use ($dispatcher): void {
            $dispatcher->dispatch(FileCreating::class, [
                'disk' => 'local',
                'path' => '/test.txt',
                'size' => 10,
            ]);
        };

        // THEN
        expect($callback)->toThrow(\RuntimeException::class, 'Database connection lost');
    });
})->covers(CancellableTrait::class, OperationCancelledException::class, EventDispatcher::class);
