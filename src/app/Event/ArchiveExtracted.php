<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Event;

use Illuminate\Contracts\Auth\Authenticatable;
use Kwaadpepper\LaravelStorageManager\Lib\Event\SmEvent;

/** @implements SmEvent<array{'path': string, 'destination': string}> */
class ArchiveExtracted implements SmEvent
{
    public function __construct(
        public readonly string $path,
        public readonly string $destination,
        public readonly ?Authenticatable $user = null,
    ) {
    }

    /**
     * @param  array{'path': string, 'destination': string}  $parameters
     */
    public static function make(?Authenticatable $user, array $parameters): self
    {
        return new self(
            path: $parameters['path'],
            destination: $parameters['destination'],
            user: $user
        );
    }
}
