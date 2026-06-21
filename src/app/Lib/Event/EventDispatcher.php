<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Event;

use Illuminate\Events\Dispatcher;
use Kwaadpepper\LaravelStorageManager\Service\AuthService;

class EventDispatcher
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly Dispatcher $dispatcher
    ) {
    }

    /**
     * @template TPayload of array<array-key,mixed>|array{}
     *
     * @param  class-string<SmEvent<TPayload>>  $eventClass
     * @param  TPayload  $parameters
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
     *
     * @param  class-string<SmEvent<TPayload>>  $eventClass
     * @param  TPayload  $parameters
     * @return SmEvent<TPayload>
     */
    private function make(string $eventClass, array $parameters): SmEvent
    {
        /** @var SmEvent<TPayload> */
        return $eventClass::make(
            $this->authService->user(),
            $parameters
        );
    }
}
