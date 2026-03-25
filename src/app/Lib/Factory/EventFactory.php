<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Factory;

use Kwaadpepper\LaravelStorageManager\Event\SmEvent;
use Kwaadpepper\LaravelStorageManager\Service\AuthService;

abstract class EventFactory
{
    /**
     * @template TEvent of \Kwaadpepper\LaravelStorageManager\Event\SmEvent
     *
     * @param  class-string<TEvent>  $eventClass
     * @param  array<string,mixed>  $parameters
     * @return TEvent
     */
    public static function make(string $eventClass, array $parameters): SmEvent
    {
        $authService = app(AuthService::class);

        // On suppose que SmEvent ou ses enfants implémentent une méthode statique make()
        return $eventClass::make($authService->user(), $parameters);
    }

    /**
     * @template TEvent of \Kwaadpepper\LaravelStorageManager\Event\SmEvent
     *
     * @param  class-string<TEvent>  $eventClass
     * @param  array<string,mixed>  $parameters
     */
    public static function dispatch(string $eventClass, array $parameters = []): void
    {
        // Ici, PHPStan comprendra que $event est une instance de $eventClass
        $event = self::make($eventClass, $parameters);

        event($event);
    }
}
