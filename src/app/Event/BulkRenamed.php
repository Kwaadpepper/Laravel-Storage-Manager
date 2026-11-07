<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Event;

use Illuminate\Contracts\Auth\Authenticatable;
use Kwaadpepper\LaravelStorageManager\Lib\Event\SmEvent;

/** @implements SmEvent<array{'renamed': array<int, array{from: string, to: string}>}> */
class BulkRenamed implements SmEvent
{
    /**
     * @param  array<int, array{from: string, to: string}>  $renamed
     */
    public function __construct(
        public readonly array $renamed,
        public readonly ?Authenticatable $user = null,
    ) {
        foreach ($this->renamed as $item) {
            if (! isset($item['from'], $item['to'])) {
                throw new \InvalidArgumentException('Each renamed item must have "from" and "to" keys.');
            }
            if (! is_string($item['from']) || ! is_string($item['to'])) {
                throw new \InvalidArgumentException('Each renamed item must have "from" and "to" as strings.');
            }
        }
    }

    /**
     * @param  array{'renamed': array<int, array{from: string, to: string}>}  $parameters
     */
    public static function make(?Authenticatable $user, array $parameters): self
    {
        return new self(
            renamed: $parameters['renamed'],
            user: $user
        );
    }
}
