<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Event;

use Illuminate\Contracts\Auth\Authenticatable;

interface SmEvent
{
    /**
     * @param  array<string,mixed>  $parameters
     */
    public static function make(?Authenticatable $user, array $parameters): self;
}
