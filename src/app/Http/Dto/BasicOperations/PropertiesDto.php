<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Exception\DomainException;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class PropertiesDto implements Dto
{
    public function __construct(
        public string $path,
        public string $basename,
        public string $dirname,
        public \DateTimeInterface $timestamp,
        public string $visibility,
        public bool $isFile,
        public bool $isDirectory,
        public ?string $extension = null,
        public ?int $size = null,
    ) {
        if ($this->isFile && ($this->extension === null || $this->size === null)) {
            throw new DomainException('For files, extension and size must be provided.');
        }
    }

    public function jsonSerialize(): mixed
    {
        $properties = [
            'path'        => $this->path,
            'basename'    => $this->basename,
            'dirname'     => $this->dirname,
            'timestamp'   => $this->timestamp->format(\DateTimeInterface::RFC3339),
            'visibility'  => $this->visibility,
            'isFile'      => $this->isFile,
            'isDirectory' => $this->isDirectory,
            'extension'   => $this->extension,
            'size'        => $this->size,
        ];

        if ($this->isDirectory) {
            unset($properties['extension'], $properties['size'], $properties['isFile']);
        }

        if ($this->isFile) {
            unset($properties['isDirectory']);
        }

        return $properties;
    }
}
