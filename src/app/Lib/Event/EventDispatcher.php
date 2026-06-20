<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Event;

use Illuminate\Events\Dispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\Event\SmEvent;
use Kwaadpepper\LaravelStorageManager\Service\AuthService;

class EventDispatcher
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly Dispatcher $dispatcher
    ) {
    }

    /**
     * @template TPayload of array<array-key,mixed>
     * @template TEvent of SmEvent<TPayload>
     *
     * @param  class-string<TEvent> $eventClass
     * @param  array<string,mixed>  $parameters
     */
    public function dispatch(string $eventClass, array $parameters = []): void
    {
        $event = $this->make(
            $eventClass,
            $parameters
        );

        $this->dispatcher->dispatch($event);
    }

    /**
     * @template TPayload of array<array-key,mixed>
     * @template TEvent of SmEvent<TPayload>
     *
     * @param  class-string<TEvent> $eventClass
     * @param  TPayload  $parameters
     *
     * @return TEvent
     */
    private function make(string $eventClass, array $parameters): SmEvent
    {
        /** @var TEvent */
        return $eventClass::make(
            $this->authService->user(),
            $parameters
        );
    }
}
