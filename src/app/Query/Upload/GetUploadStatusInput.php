<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Upload;

use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;

final readonly class GetUploadStatusInput implements QueryInput
{
    public function __construct(
        public UploadId $uploadId,
    ) {
    }

    public function getDisk(): ?Disk
    {
        return null;
    }

    public function getPath(): ?Path
    {
        return null;
    }
}
