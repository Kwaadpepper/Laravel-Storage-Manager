<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Upload;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;

class UploadPathResolver
{
    private const METADATA_FILE = 'metadata.json';
    private const STATUS_FILE = 'status.json';
    private const ASSEMBLED_FILE = 'assembled_file';

    public function __construct(private readonly ConfigRepository $configRepository)
    {
    }

    public function getDisk(): Filesystem
    {
        $diskName = $this->configRepository->getUploadTempDisk();
        if (! $diskName) {
            return Storage::build([
                'driver' => 'local',
                'root'   => sys_get_temp_dir(),
            ]);
        }

        return Storage::disk($diskName);
    }

    public function getBasePath(): string
    {
        return $this->configRepository->getUploadTempPath();
    }

    public function getUploadDir(UploadId $uploadId): string
    {
        return $this->getBasePath() . '/' . $uploadId->value;
    }

    public function getMetadataPath(UploadId $uploadId): string
    {
        return $this->getUploadDir($uploadId) . '/' . self::METADATA_FILE;
    }

    public function getStatusPath(UploadId $uploadId): string
    {
        return $this->getUploadDir($uploadId) . '/' . self::STATUS_FILE;
    }

    public function getChunkPath(UploadId $uploadId, int $chunkIndex): string
    {
        return $this->getUploadDir($uploadId) . '/chunk_' . $chunkIndex;
    }

    public function getAssembledFilePath(UploadId $uploadId): string
    {
        return $this->getUploadDir($uploadId) . '/' . self::ASSEMBLED_FILE;
    }

    public function getAbsolutePath(string $relativePath): string
    {
        return $this->getDisk()->path($relativePath);
    }

    /**
     * @return list<string>
     */
    public function getUploadDirs(): array
    {
        return array_values($this->getDisk()->directories($this->getBasePath()));
    }
}
