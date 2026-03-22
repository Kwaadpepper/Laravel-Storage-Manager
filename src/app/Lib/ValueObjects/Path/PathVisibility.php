<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

enum PathVisibility: string
{
    case PUBLIC  = 'public';
    case PRIVATE = 'private';
}
