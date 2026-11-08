<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Enum;

enum FilenamePolicy: string
{
    case STRICT     = 'strict';
    case PERMISSIVE = 'permissive';

    public function illegalCharsRegex(): string
    {
        return match ($this) {
            self::STRICT     => '/[<>:"\/\\\\|?*\x00-\x1F\x7F]/u',
            self::PERMISSIVE => '/[<>"\/\\\\|?*\x00-\x1F\x7F]/u',
        };
    }
}
