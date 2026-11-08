<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects;

use Kwaadpepper\LaravelStorageManager\Config\UploadConfig;
use Kwaadpepper\LaravelStorageManager\Enum\DuplicatePolicy;
use Kwaadpepper\LaravelStorageManager\Enum\FilenamePolicy;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;

final readonly class Disk
{
    public ?string $root;

    /**
     * @param  list<string>|null  $allowedExtensions
     */
    public function __construct(
        public string $driver,
        public string $name,
        public bool $throw = false,
        public bool $report = false,
        public bool $readOnly = false,
        ?string $root = null,
        public ?array $allowedExtensions = null,
        public bool $trash = true,
        public string $trashFolder = '.trash',
        public int $trashRetentionDays = 30,
        public ?int $maxFileSize = null,
        public ?int $quota = null,
        public ?DuplicatePolicy $duplicatePolicy = null,
        public FilenamePolicy $filenamePolicy = FilenamePolicy::STRICT,
    ) {
        if (empty($this->driver)) {
            throw new IllegalDomainStateException('Disk driver cannot be empty.');
        }
        if (empty($this->name)) {
            throw new IllegalDomainStateException('Disk name cannot be empty.');
        }
        if ($this->trashRetentionDays < 0) {
            throw new \InvalidArgumentException('Trash retention days must be non-negative.');
        }
        if ($this->maxFileSize !== null && $this->maxFileSize <= 0) {
            throw new \InvalidArgumentException('Max file size must be greater than zero.');
        }
        if ($this->quota !== null && $this->quota <= 0) {
            throw new \InvalidArgumentException('Quota must be greater than zero.');
        }

        $this->root = $root !== null && mb_trim($root) !== '' && mb_trim($root) !== '/'
            ? '/' . trim(mb_trim($root), '/')
            : null;
    }

    public function isLocal(): bool
    {
        return $this->driver === 'local';
    }

    public function isRemote(): bool
    {
        return $this->driver !== 'local';
    }

    /**
     * @throws FileOperationException
     */
    public function assertWritable(): void
    {
        if ($this->readOnly) {
            throw new FileOperationException(FileOperationError::READ_ONLY_DISK);
        }
    }

    public function isExtensionAllowed(string $extension, ?UploadConfig $fallbackUploadConfig = null): bool
    {
        $normalized = strtolower(ltrim(mb_trim($extension), '.'));
        if ($normalized === '' || in_array($normalized, UploadConfig::UNSUPPORTED_EXTENSIONS, true)) {
            return false;
        }

        if ($this->allowedExtensions !== null) {
            $normalizedAllowed = array_map(
                static fn (string $ext): string => strtolower(ltrim(mb_trim($ext), '.')),
                $this->allowedExtensions
            );

            return in_array($normalized, $normalizedAllowed, true);
        }

        return $fallbackUploadConfig?->isExtensionAllowed($extension) ?? true;
    }
}
