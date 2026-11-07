<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Event;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Lib\Event\SmEvent;

/** @implements SmEvent<array{'path': string}> */
class ZipCreated implements SmEvent
{
    public function __construct(
        public readonly string $path,
        public readonly ?Authenticatable $user = null,
    ) {
    }

    /**
     * @param  array{'path': string}  $parameters
     */
    public static function make(?Authenticatable $user, array $parameters): self
    {
        return new self(
            path: Arr::string($parameters, 'path'),
            user: $user
        );
    }
}
