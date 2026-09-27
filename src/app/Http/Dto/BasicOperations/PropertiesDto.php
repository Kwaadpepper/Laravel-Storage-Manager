<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class PropertiesDto implements Dto
{
    /**
     * @param  array{width: int, height: int, aspectRatio: string}|null  $dimensions
     * @param  array<string, mixed>|null  $exif
     * @param  array<string, string|int|float|bool>|null  $rawExif
     */
    public function __construct(
        public string $path,
        public string $basename,
        public string $dirname,
        public \DateTimeInterface $timestamp,
        public ?string $visibility,
        public bool $isFile,
        public bool $isDirectory,
        public ?string $extension = null,
        public ?int $size = null,
        public ?string $mimeType = null,
        public ?string $publicUrl = null,
        public ?string $checksum = null,
        public ?array $dimensions = null,
        public ?array $exif = null,
        public ?array $rawExif = null,
    ) {
        if ($this->isFile && ($this->extension === null || $this->size === null)) {
            throw new \InvalidArgumentException('For files, extension and size must be provided.');
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
            'mimeType'    => $this->mimeType,
            'publicUrl'   => $this->publicUrl,
            'checksum'    => $this->checksum,
            'dimensions'  => $this->dimensions,
            'exif'        => $this->exif,
            'rawExif'     => $this->rawExif,
        ];

        if ($this->isDirectory) {
            unset(
                $properties['extension'],
                $properties['size'],
                $properties['isFile'],
                $properties['mimeType'],
                $properties['publicUrl'],
                $properties['checksum'],
                $properties['dimensions'],
                $properties['exif'],
                $properties['rawExif']
            );
        }

        if ($this->isFile) {
            unset($properties['isDirectory']);
        }

        return $properties;
    }
}
