<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Factory;

use Kwaadpepper\LaravelStorageManager\Event\SmEvent;
use Kwaadpepper\LaravelStorageManager\Service\AuthService;

final abstract class EventFactory
{
    /**
     * @param  class-string<SmEvent>  $eventClass
     * @param  array<string,mixed>  $parameters
     * @return TEvent
     *
     * @template TEvent of SmEvent
     */
    public static function make(string $eventClass, array $parameters): SmEvent
    {
        $authService = app(AuthService::class);

        return $eventClass::make($authService->user(), $parameters);
    }

    /**
     * @param  class-string<SmEvent>  $eventClass
     * @param  array<string,mixed>  $parameters
     */
    public static function dispatch(string $eventClass, array $parameters = []): void
    {
        $event = self::make($eventClass, $parameters);

        event($event);
    }
}
