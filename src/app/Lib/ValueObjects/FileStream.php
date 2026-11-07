<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects;

use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;

final readonly class FileStream
{
    /**
     * @param  resource  $resource
     */
    public function __construct(
        public mixed $resource,
    ) {
        if (! is_resource($this->resource) || get_resource_type($this->resource) !== 'stream') {
            throw new IllegalDomainStateException('The provided value is not a valid stream resource.');
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];

        if (! str_contains($mode, 'r') && ! str_contains($mode, '+')) {
            throw new IllegalDomainStateException('The stream is not readable.');
        }
    }

    public static function fromPath(string $path, string $mode = 'rb'): self
    {
        if (! file_exists($path) || ! is_readable($path)) {
            throw new IllegalDomainStateException(sprintf('File does not exist or is not readable: %s', $path));
        }

        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        $resource = @fopen($path, $mode);
        if (! is_resource($resource)) {
            throw new IllegalDomainStateException(sprintf('Unable to open file stream for path: %s', $path));
        }

        return new self($resource);
    }

    public static function fromString(string $content): self
    {
        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        $resource = @fopen('php://temp', 'r+b');
        if (! is_resource($resource)) {
            throw new IllegalDomainStateException('Unable to create temporary memory stream.');
        }

        fwrite($resource, $content);
        rewind($resource);

        return new self($resource);
    }

    /**
     * @return resource
     */
    public function stream(): mixed
    {
        if (! is_resource($this->resource) || get_resource_type($this->resource) !== 'stream') {
            throw new IllegalDomainStateException('The stream resource is no longer valid.');
        }

        return $this->resource;
    }

    public function size(): int
    {
        if (! is_resource($this->resource)) {
            return 0;
        }

        $stats = fstat($this->resource) ?: [];

        return $stats['size'] ?? 0;
    }

    public function isSeekable(): bool
    {
        return is_resource($this->resource) && (bool) stream_get_meta_data($this->resource)['seekable'];
    }

    public function eof(): bool
    {
        return ! is_resource($this->resource) || feof($this->resource);
    }

    public function seek(int $offset): void
    {
        if ($offset <= 0 || ! is_resource($this->resource)) {
            return;
        }

        if ($this->isSeekable()) {
            fseek($this->resource, $offset);

            return;
        }

        $toSkip = $offset;
        while ($toSkip > 0 && is_resource($this->resource) && ! feof($this->resource)) {
            $chunk = fread($this->resource, min(8192, $toSkip));
            if ($chunk === false || $chunk === '') {
                break;
            }
            $toSkip -= strlen($chunk);
        }
    }

    public function read(int $length): string
    {
        if (! is_resource($this->resource) || $length <= 0) {
            return '';
        }

        $chunk = fread($this->resource, $length);

        return $chunk !== false ? $chunk : '';
    }

    public function getContents(?int $maxBytes = null): string
    {
        if (! is_resource($this->resource)) {
            return '';
        }

        if ($maxBytes === null && $this->isSeekable()) {
            rewind($this->resource);
        }

        return stream_get_contents($this->resource, $maxBytes ?? -1) ?: '';
    }

    public function copyToPath(string $targetPath, ?int $maxBytes = null): void
    {
        if (! is_resource($this->resource)) {
            throw new IllegalDomainStateException('Source stream is not a valid resource.');
        }

        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        $dest = @fopen($targetPath, 'wb');
        if (! is_resource($dest)) {
            throw new IllegalDomainStateException(sprintf('Unable to open destination file for writing: %s', $targetPath));
        }

        try {
            stream_copy_to_stream($this->resource, $dest, $maxBytes ?? -1);
        } finally {
            fclose($dest);
        }
    }

    public function hash(string $algo = 'md5'): string
    {
        if (! is_resource($this->resource)) {
            throw new IllegalDomainStateException('Cannot hash an invalid or closed stream.');
        }

        if ($this->isSeekable()) {
            rewind($this->resource);
        }

        $ctx = hash_init($algo);
        hash_update_stream($ctx, $this->resource);

        return hash_final($ctx);
    }

    public function close(): void
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
