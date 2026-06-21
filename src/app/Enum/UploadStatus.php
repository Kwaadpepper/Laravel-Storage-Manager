<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Enum;

enum UploadStatus: string
{
    case WAITING      = 'waiting';
    case ASSEMBLING   = 'assembling';
    case TRANSFERRING = 'transferring';
}
