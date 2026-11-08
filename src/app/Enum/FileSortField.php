<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Enum;

enum FileSortField: string
{
    case Name         = 'name';
    case Size         = 'size';
    case Extension    = 'extension';
    case Visibility   = 'visibility';
    case LastModified = 'last_modified';
}
