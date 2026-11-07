<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\Upload;

use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadMetadata;

final readonly class InitUploadInput implements ActionInput
{
    public function __construct(
        public UploadId $uploadId,
        public UploadMetadata $metadata,
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
