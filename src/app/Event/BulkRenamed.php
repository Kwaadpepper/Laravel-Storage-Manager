<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Event;

use Illuminate\Contracts\Auth\Authenticatable;
use Kwaadpepper\LaravelStorageManager\Lib\Event\SmEvent;

/** @implements SmEvent<array{'renamed': array<int, array{from: string, to: string}>}> */
class BulkRenamed implements SmEvent
{
    /**
     * @param  array<int, array{from: string, to: string}>  $renamed
     */
    public function __construct(
        public readonly array $renamed,
        public readonly ?Authenticatable $user = null,
    ) {
    }

    /**
     * @param  array{'renamed': array<int, array{from: string, to: string}>}  $parameters
     */
    public static function make(?Authenticatable $user, array $parameters): self
    {
        return new self(
            renamed: $parameters['renamed'],
            user: $user
        );
    }
}
