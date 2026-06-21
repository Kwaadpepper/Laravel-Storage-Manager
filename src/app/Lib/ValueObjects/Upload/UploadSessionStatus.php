<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload;

use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Enum\UploadStatus;

class UploadSessionStatus
{
    public function __construct(
        public readonly UploadStatus $status,
        public readonly int $progress,
    ) {
        if ($this->progress < 0 || $this->progress > 100) {
            throw new \InvalidArgumentException('progress must be between 0 and 100.');
        }
    }

    public static function waiting(int $progress = 0): self
    {
        return new self(
            status: UploadStatus::WAITING,
            progress: $progress,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $uploadStatus = UploadStatus::tryFrom(Arr::string($data, 'status'));

        if (! $uploadStatus instanceof UploadStatus) {
            throw new \InvalidArgumentException('status must be an instance of UploadStatus.');
        }

        return new self(
            status: $uploadStatus,
            progress: Arr::integer($data, 'progress', 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status'   => $this->status->value,
            'progress' => $this->progress,
        ];
    }
}
