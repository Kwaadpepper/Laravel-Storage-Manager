<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Event;

trait CancellableTrait
{
    private bool $cancelled = false;

    private ?string $cancellationReason = null;

    public function cancel(?string $reason = null): void
    {
        $this->cancelled          = true;
        $this->cancellationReason = $reason;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    public function cancellationReason(): ?string
    {
        return $this->cancellationReason;
    }
}
