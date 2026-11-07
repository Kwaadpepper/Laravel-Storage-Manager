<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathProperties;

final readonly class PropertiesDto implements Dto
{
    public static function fromPathProperties(PathProperties $properties): self
    {
        return new self(
            path: (string) $properties->path,
            basename: $properties->basename,
            dirname: $properties->dirname,
            timestamp: $properties->timestamp,
            visibility: $properties->visibility?->value,
            isFile: $properties->isFile,
            isDirectory: $properties->isDirectory,
            extension: $properties instanceof FilePathProperties ? $properties->extension : null,
            size: $properties instanceof FilePathProperties ? $properties->size : null,
            mimeType: $properties instanceof FilePathProperties ? $properties->mimeType : null,
            publicUrl: $properties instanceof FilePathProperties ? $properties->publicUrl : null,
            checksum: $properties instanceof FilePathProperties ? $properties->checksum : null,
            dimensions: $properties instanceof FilePathProperties ? $properties->dimensions : null,
            exif: $properties instanceof FilePathProperties ? $properties->exif : null,
            rawExif: $properties instanceof FilePathProperties ? $properties->rawExif : null,
        );
    }

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
