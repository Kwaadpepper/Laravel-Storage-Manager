<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Event;

use Illuminate\Contracts\Auth\Authenticatable;
use Kwaadpepper\LaravelStorageManager\Lib\Event\SmEvent;

/** @implements SmEvent<array{'path': string, 'trashId': string}> */
class DirectoryTrashed implements SmEvent
{
    public function __construct(
        public readonly string $path,
        public readonly string $trashId,
        public readonly ?Authenticatable $user = null,
    ) {
    }

    public static function make(?Authenticatable $user, array $parameters): self
    {
        return new self(
            user: $user,
            path: $parameters['path'],
            trashId: $parameters['trashId'],
        );
    }
}
