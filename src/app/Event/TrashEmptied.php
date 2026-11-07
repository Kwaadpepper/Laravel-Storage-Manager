<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Event;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Lib\Event\SmEvent;

/** @implements SmEvent<array{'disk': string, 'count': int}> */
class TrashEmptied implements SmEvent
{
    public function __construct(
        public readonly string $disk,
        public readonly int $count,
        public readonly ?Authenticatable $user = null,
    ) {
    }

    public static function make(?Authenticatable $user, array $parameters): self
    {
        return new self(
            user: $user,
            disk: Arr::string($parameters, 'disk'),
            count: Arr::integer($parameters, 'count'),
        );
    }
}
