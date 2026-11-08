<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Enum;

enum UploadLockDriver: string
{
    case AUTO  = 'auto';
    case CACHE = 'cache';
    case FLOCK = 'flock';
}
