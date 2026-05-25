<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects;

use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;

final class FileStream
{
    /**
     * @param  resource  $stream
     */
    public function __construct(
        private mixed $stream,
    ) {
        if (! is_resource($this->stream) || get_resource_type($this->stream) !== 'stream') {
            throw new IllegalDomainStateException('The provided value is not a valid stream resource.');
        }
    }

    /**
     * @return resource
     */
    public function stream()
    {
        if (! is_resource($this->stream) || get_resource_type($this->stream) !== 'stream') {
            throw new IllegalDomainStateException('The stream resource is no longer valid.');
        }

        return $this->stream;
    }

    public function size(): int
    {
        if (! is_resource($this->stream)) {
            return 0;
        }

        $stat = fstat($this->stream) ?: [];

        return $stat['size'] ?? 0;
    }
}
