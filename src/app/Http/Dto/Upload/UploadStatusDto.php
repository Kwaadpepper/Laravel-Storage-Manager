<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\Upload;

use Kwaadpepper\LaravelStorageManager\Enum\UploadStatus;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class UploadStatusDto implements Dto
{
    public function __construct(
        public UploadStatus $status,
        public int $progress,
    ) {
        if ($this->progress < 0 || $this->progress > 100) {
            throw new \InvalidArgumentException('progress must be between 0 and 100.');
        }
    }

    public function jsonSerialize(): mixed
    {
        return [
            'status'   => $this->status->value,
            'progress' => $this->progress,
        ];
    }
}
