<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Config;

final readonly class AuthConfig
{
    public function __construct(
        public bool $enabled,
        public string $guard,
        public bool $allowByDefault,
    ) {
    }
}
