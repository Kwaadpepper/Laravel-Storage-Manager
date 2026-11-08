<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Event;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Lib\Event\CancellableEvent;
use Kwaadpepper\LaravelStorageManager\Lib\Event\CancellableTrait;

/** @implements CancellableEvent<array{'disk': string, 'path': string}> */
class DirectoryCreating implements CancellableEvent
{
    use CancellableTrait;

    public function __construct(
        public readonly string $disk,
        public readonly string $path,
        public readonly ?Authenticatable $user = null,
    ) {
    }

    public static function make(?Authenticatable $user, array $parameters): self
    {
        return new self(
            disk: Arr::string($parameters, 'disk'),
            path: Arr::string($parameters, 'path'),
            user: $user,
        );
    }
}
