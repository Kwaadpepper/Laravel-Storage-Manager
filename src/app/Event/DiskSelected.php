<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Event;

use Illuminate\Contracts\Auth\Authenticatable;

final class DiskSelected implements SmEvent
{
    public function __construct(
        public readonly ?Authenticatable $user,
        public readonly string $disk
    ) {
    }

    public static function make(?Authenticatable $user, array $parameters): self
    {
        return new self(
            user: $user,
            disk: $parameters['disk'] ?? '',
        );
    }
}
