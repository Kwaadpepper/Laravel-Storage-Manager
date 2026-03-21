<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Event;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Interface SmEvent
 *
 * @template TEvent of object
 */
interface SmEvent
{
    /**
     * @param  array<string,mixed>  $parameters
     * @return TEvent
     */
    public static function make(?Authenticatable $user, array $parameters): self;
}
