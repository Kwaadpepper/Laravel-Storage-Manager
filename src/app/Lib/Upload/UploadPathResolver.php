<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Upload;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;

class UploadPathResolver
{
    private const string METADATA_FILE = 'metadata.json';

    private const string STATUS_FILE = 'status.json';

    private const string ASSEMBLED_FILE = 'assembled_file';

    public function __construct(
        private readonly StorageManagerConfig $config,
        private readonly FilesystemFactory $filesystemFactory,
    ) {
    }

    public function getDisk(): Filesystem
    {
        $diskName = $this->config->upload->tempDisk;
        if (! $diskName) {
            if ($this->filesystemFactory instanceof FilesystemManager) {
                return $this->filesystemFactory->build([
                    'driver' => 'local',
                    'root'   => sys_get_temp_dir(),
                ]);
            }

            return $this->filesystemFactory->disk();
        }

        return $this->filesystemFactory->disk($diskName);
    }

    public function getBasePath(): string
    {
        return $this->config->upload->tempPath;
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
