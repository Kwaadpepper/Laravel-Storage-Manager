<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Event;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Lib\Event\SmEvent;

/** @implements SmEvent<array{'source_path': string, 'target_path': string}> */
class DirectoryCopied implements SmEvent
{
    public function __construct(
        public readonly string $sourcePath,
        public readonly string $targetPath,
        public readonly ?Authenticatable $user = null,
    ) {
    }

    public static function make(?Authenticatable $user, array $parameters): self
    {
        return new self(
            user: $user,
            sourcePath: Arr::string($parameters, 'source_path'),
            targetPath: Arr::string($parameters, 'target_path')
        );
    }
}
