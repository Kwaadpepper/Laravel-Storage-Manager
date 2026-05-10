<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Factory;

use Kwaadpepper\LaravelStorageManager\Event\SmEvent;
use Kwaadpepper\LaravelStorageManager\Service\AuthService;

abstract class EventFactory
{
    /**
     * @template TEvent of SmEvent
     *
     * @param  class-string<TEvent>  $eventClass
     * @param  array<string,mixed>  $parameters
     */
    public static function make(string $eventClass, array $parameters): SmEvent
    {
        $authService = app(AuthService::class);

        return $eventClass::make($authService->user(), $parameters);
    }

    /**
     * @template TEvent of SmEvent
     *
     * @param  class-string<TEvent>  $eventClass
     * @param  array<string,mixed>  $parameters
     */
    public static function dispatch(string $eventClass, array $parameters = []): void
    {
        $event = self::make($eventClass, $parameters);

        event($event);
    }
}
