<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

readonly class FilePathProperties implements PathProperties
{
    public bool $isDirectory;

    public bool $isFile;

    /**
     * @param  array{width: int, height: int, aspectRatio: string}|null  $dimensions
     * @param  array<string, mixed>|null  $exif
     * @param  array<string, string|int|float|bool>|null  $rawExif
     */
    public function __construct(
        public Path $path,
        public string $basename,
        public string $dirname,
        public string $extension,
        public string $filename,
        public int $size,
        public \DateTimeInterface $timestamp,
        public ?PathVisibility $visibility,
        public ?string $mimeType = null,
        public ?string $publicUrl = null,
        public ?string $checksum = null,
        public ?array $dimensions = null,
        public ?array $exif = null,
        public ?array $rawExif = null,
    ) {
        $this->isFile      = true;
        $this->isDirectory = false;
    }
}
