<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Event;

use Illuminate\Contracts\Auth\Authenticatable;

final class FileManagerShowed implements SmEvent
{
    public function __construct(
        public readonly ?Authenticatable $user = null,
    ) {
    }

    public function make(?Authenticatable $user, array $parameters): self
    {
        return new self();
    }
}
