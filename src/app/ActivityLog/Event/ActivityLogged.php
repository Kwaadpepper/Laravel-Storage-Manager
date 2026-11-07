<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\ActivityLog\Event;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Lib\Event\SmEvent;

/**
 * @implements SmEvent<array{'action': string, 'disk': string, 'path': string, 'timestamp': string}>
 */
final readonly class ActivityLogged implements SmEvent
{
    public function __construct(
        public string $action,
        public string $disk,
        public string $path,
        public CarbonImmutable $timestamp,
        public ?Authenticatable $user = null,
    ) {
    }

    public static function make(?Authenticatable $user, array $parameters): self
    {
        $timestampStr = Arr::string($parameters, 'timestamp');
        $timestamp    = $timestampStr !== '' ? CarbonImmutable::parse($timestampStr) : CarbonImmutable::now();

        return new self(
            action: Arr::string($parameters, 'action'),
            disk: Arr::string($parameters, 'disk'),
            path: Arr::string($parameters, 'path'),
            timestamp: $timestamp,
            user: $user,
        );
    }
}
