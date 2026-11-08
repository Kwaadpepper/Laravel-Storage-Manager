<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Event;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Lib\Event\CancellableEvent;
use Kwaadpepper\LaravelStorageManager\Lib\Event\CancellableTrait;

/** @implements CancellableEvent<array{'disk': string, 'archive': string, 'destination': string}> */
class ArchiveExtracting implements CancellableEvent
{
    use CancellableTrait;

    public function __construct(
        public readonly string $disk,
        public readonly string $archive,
        public readonly string $destination,
        public readonly ?Authenticatable $user = null,
    ) {
    }

    public static function make(?Authenticatable $user, array $parameters): self
    {
        return new self(
            disk: Arr::string($parameters, 'disk'),
            archive: Arr::string($parameters, 'archive'),
            destination: Arr::string($parameters, 'destination'),
            user: $user,
        );
    }
}
