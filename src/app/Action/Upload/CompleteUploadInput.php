<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\Upload;

use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Enum\DuplicatePolicy;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;

final readonly class CompleteUploadInput implements ActionInput
{
    /**
     * @param  (callable(string, int): void)|null  $onProgress
     */
    public function __construct(
        public UploadId $uploadId,
        public Disk $disk,
        public Path $destinationDir,
        public string $fileName,
        public ?DuplicatePolicy $requestedPolicy = null,
        public mixed $onProgress = null,
    ) {
    }

    public function getDisk(): Disk
    {
        return $this->disk;
    }

    public function getPath(): Path
    {
        return $this->destinationDir;
    }
}
