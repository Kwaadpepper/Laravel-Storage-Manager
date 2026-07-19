<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Enum;

enum DuplicatePolicy: string
{
    case OVERWRITE   = 'overwrite';
    case AUTO_RENAME = 'auto_rename';
    case ERROR       = 'error';
}
