<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Event;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Lib\Event\CancellableEvent;
use Kwaadpepper\LaravelStorageManager\Lib\Event\CancellableTrait;
use Kwaadpepper\LaravelStorageManager\Lib\Event\SmEvent;

/**
 * @implements CancellableEvent<array{disk: string, paths: array<string>, destination: string}>
 * @implements SmEvent<array{disk: string, paths: array<string>, destination: string}>
 */
class ZipCreating implements CancellableEvent, SmEvent
{
    use CancellableTrait;

    /**
     * @param  array<string>  $paths
     */
    public function __construct(
        public readonly string $disk,
        public readonly array $paths,
        public readonly string $destination,
        public readonly ?Authenticatable $user = null,
    ) {
    }

    public static function make(?Authenticatable $user, array $parameters): self
    {
        /** @var array<string> $paths */
        $paths = Arr::get($parameters, 'paths', []);

        return new self(
            disk: Arr::string($parameters, 'disk'),
            paths: $paths,
            destination: Arr::string($parameters, 'destination'),
            user: $user,
        );
    }
}
