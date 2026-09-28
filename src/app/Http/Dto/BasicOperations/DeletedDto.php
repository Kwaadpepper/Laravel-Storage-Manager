<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class DeletedDto implements Dto
{
    public function __construct(
        public bool $trashed = false,
        public ?string $trashId = null,
    ) {
    }

    /**
     * @return array{trashed?: bool, trashId?: ?string}
     */
    public function jsonSerialize(): mixed
    {
        if (! $this->trashed) {
            return [];
        }

        return [
            'trashed' => $this->trashed,
            'trashId' => $this->trashId,
        ];
    }
}
