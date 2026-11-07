<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\ActivityLog;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Kwaadpepper\LaravelStorageManager\ActivityLog\Event\ActivityLogged;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Psr\Log\LoggerInterface;

class ActivityLogger
{
    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param  Action<ActionInput, mixed>  $action
     */
    public function record(
        ?Authenticatable $user,
        Action $action,
        ActionInput $input,
        mixed $result,
    ): void {
        $actionClass = $action::class;
        $actionName  = class_basename($actionClass);
        $disk        = $input->getDisk()?->name;
        $path        = $input->getPath()?->value;

        $this->logger->info("[StorageManager] Activity Logged: {$actionName}", [
            'user'   => $user?->getAuthIdentifier(),
            'action' => $actionClass,
            'disk'   => $disk,
            'path'   => $path,
        ]);

        $this->eventDispatcher->dispatch(
            ActivityLogged::class,
            [
                'action'    => $actionName,
                'disk'      => $disk ?? '',
                'path'      => $path ?? '',
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ]
        );
    }
}
