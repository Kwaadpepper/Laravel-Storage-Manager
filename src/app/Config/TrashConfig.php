<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Config;

final readonly class TrashConfig
{
    public function __construct(
        public bool $enabled,
        public string $folder,
        public int $retentionDays
    ) {
        $trimmedFolder = trim($this->folder);
        if (str_contains($trimmedFolder, '..') || str_contains($trimmedFolder, '\\')) {
            throw new \UnexpectedValueException(
                "Invalid trash folder '{$this->folder}'. Trash folder cannot contain '..' or '\\'."
            );
        }
    }
}
