<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Config;

final readonly class RouteConfig
{
    /**
     * @param  list<string>  $middleware
     */
    public function __construct(
        public string $prefix,
        public array $middleware
    ) {
        $trimmedPrefix = trim($this->prefix);
        if (str_contains($trimmedPrefix, '..') || str_contains($trimmedPrefix, '\\') || str_contains($trimmedPrefix, '//')) {
            throw new \UnexpectedValueException(
                "Invalid route prefix '{$this->prefix}'. Route prefix cannot contain '..', '\\', or '//'."
            );
        }
    }
}
