<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Event;

use Illuminate\Contracts\Auth\Authenticatable;

/** @template TPayload of array<array-key,mixed> */
interface SmEvent
{
    /**
     * @param  TPayload  $parameters
     * @return self<TPayload>
     */
    public static function make(?Authenticatable $user, array $parameters): self;
}
