<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Event;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Lib\Event\CancellableEvent;
use Kwaadpepper\LaravelStorageManager\Lib\Event\CancellableTrait;

/** @implements CancellableEvent<array{'disk': string, 'count': int}> */
class TrashEmptying implements CancellableEvent
{
    use CancellableTrait;

    public function __construct(
        public readonly string $disk,
        public readonly int $count,
        public readonly ?Authenticatable $user = null,
    ) {
    }

    public static function make(?Authenticatable $user, array $parameters): self
    {
        return new self(
            disk: Arr::string($parameters, 'disk'),
            count: Arr::integer($parameters, 'count'),
            user: $user,
        );
    }
}
