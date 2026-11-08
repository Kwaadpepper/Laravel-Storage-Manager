<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Event;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Lib\Event\CancellableEvent;
use Kwaadpepper\LaravelStorageManager\Lib\Event\CancellableTrait;

/** @implements CancellableEvent<array{'disk': string, 'renames': array<int, array{from: string, to: string}>}> */
class BulkRenaming implements CancellableEvent
{
    use CancellableTrait;

    /**
     * @param  array<int, array{from: string, to: string}>  $renames
     */
    public function __construct(
        public readonly string $disk,
        public readonly array $renames,
        public readonly ?Authenticatable $user = null,
    ) {
    }

    public static function make(?Authenticatable $user, array $parameters): self
    {
        /** @var array<int, array{from: string, to: string}> $renames */
        $renames = Arr::get($parameters, 'renames', []);

        return new self(
            disk: Arr::string($parameters, 'disk'),
            renames: $renames,
            user: $user,
        );
    }
}
