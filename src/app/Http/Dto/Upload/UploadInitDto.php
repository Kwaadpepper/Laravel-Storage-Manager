<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\Upload;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class UploadInitDto implements Dto
{
    public function __construct(
        public string $uploadId,
    ) {
        if (empty($this->uploadId)) {
            throw new \InvalidArgumentException('uploadId cannot be empty.');
        }
    }

    public function jsonSerialize(): mixed
    {
        return [
            'upload_id' => $this->uploadId,
        ];
    }
}
