<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\Upload;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class UploadSuccessDto implements Dto
{
    public function __construct(
        public bool $success = true,
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'success' => $this->success,
        ];
    }
}
