<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Event;

/**
 * @template TPayload of array<array-key, mixed>
 *
 * @extends SmEvent<TPayload>
 */
interface CancellableEvent extends SmEvent
{
    public function cancel(?string $reason = null): void;

    public function isCancelled(): bool;

    public function cancellationReason(): ?string;
}
