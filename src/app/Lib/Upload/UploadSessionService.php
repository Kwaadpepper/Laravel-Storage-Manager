<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Upload;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Enum\UploadStatus;
use Kwaadpepper\LaravelStorageManager\Exception\Upload\AssembleFailedException;
use Kwaadpepper\LaravelStorageManager\Exception\Upload\MissingChunkException;
use Kwaadpepper\LaravelStorageManager\Exception\Upload\MissingMetadataException;
use Kwaadpepper\LaravelStorageManager\Exception\Upload\TransferFailedException;
use Kwaadpepper\LaravelStorageManager\Lib\Stream\UploadProgressFilter;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadMetadata;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadSessionStatus;

class UploadSessionService
{
    private const METADATA_FILE = 'metadata.json';

    public function __construct(private readonly UploadPathResolver $pathResolver)
    {
    }

    public function createSession(UploadId $uploadId, UploadMetadata $metadata): void
    {
        $disk = $this->pathResolver->getDisk();
        $disk->makeDirectory($this->pathResolver->getUploadDir($uploadId));
        $this->writeMetadata($uploadId, $metadata);
    }

    public function writeMetadata(UploadId $uploadId, UploadMetadata $metadata): void
    {
        $disk = $this->pathResolver->getDisk();
        $disk->put(
            $this->pathResolver->getMetadataPath($uploadId),
            json_encode($metadata->toArray(), JSON_THROW_ON_ERROR)
        );
    }

    public function sessionExists(UploadId $uploadId): bool
    {
        return $this->pathResolver->getDisk()->exists(
            $this->pathResolver->getUploadDir($uploadId)
        );
    }

    public function storeChunk(UploadId $uploadId, int $chunkIndex, UploadedFile | File | string $file): void
    {
        $this->pathResolver->getDisk()->putFileAs(
            $this->pathResolver->getUploadDir($uploadId),
            $file,
            'chunk_' . $chunkIndex
        );
    }

    public function chunkExists(UploadId $uploadId, int $chunkIndex): bool
    {
        return $this->pathResolver->getDisk()->exists(
            $this->pathResolver->getChunkPath($uploadId, $chunkIndex)
        );
    }

    public function readStatus(UploadId $uploadId): ?UploadSessionStatus
    {
        $disk = $this->pathResolver->getDisk();
        $path = $this->pathResolver->getStatusPath($uploadId);
        if (! $disk->exists($path)) {
            return null;
        }
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $disk->get($path), true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? UploadSessionStatus::fromArray($data) : null;
    }

    public function writeStatus(UploadId $uploadId, UploadStatus $status, int $progress): void
    {
        $disk          = $this->pathResolver->getDisk();
        $sessionStatus = new UploadSessionStatus($status, $progress);
        $disk->put(
            $this->pathResolver->getStatusPath($uploadId),
            json_encode($sessionStatus->toArray(), JSON_THROW_ON_ERROR)
        );
    }

    public function deleteSession(UploadId $uploadId): void
    {
        $this->pathResolver->getDisk()->deleteDirectory(
            $this->pathResolver->getUploadDir($uploadId)
        );
    }

    public function deleteChunk(UploadId $uploadId, int $chunkIndex): void
    {
        $this->pathResolver->getDisk()->delete(
            $this->pathResolver->getChunkPath($uploadId, $chunkIndex)
        );
    }

    public function readMetadataFromDir(string $dir): ?UploadMetadata
    {
        $disk = $this->pathResolver->getDisk();
        $path = $dir . '/' . self::METADATA_FILE;
        if (! $disk->exists($path)) {
            return null;
        }
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $disk->get($path), true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? UploadMetadata::fromArray($data) : null;
    }

    public function readMetadata(UploadId $uploadId): ?UploadMetadata
    {
        return $this->readMetadataFromDir(
            $this->pathResolver->getUploadDir($uploadId)
        );
    }

    /**
     * Verify all chunks exist, assemble them, stream to final destination, and clean up.
     * Throws an exception if any chunk is missing.
     */
    public function assembleAndTransfer(UploadId $uploadId, Path $finalDestination, string $diskName): void
    {
        $metadata = $this->readMetadata($uploadId);
        if (! $metadata) {
            throw new MissingMetadataException('Metadata not found for upload session');
        }

        $totalChunks = $metadata->totalChunks;

        for ($i = 0; $i < $totalChunks; $i++) {
            if (! $this->chunkExists($uploadId, $i)) {
                throw new MissingChunkException("Missing chunk {$i}");
            }
        }

        $this->writeStatus($uploadId, UploadStatus::ASSEMBLING, 0);
        $assembledFilePath = $this->pathResolver->getAbsolutePath(
            $this->pathResolver->getAssembledFilePath($uploadId)
        );

        $this->assembleChunks($uploadId, $totalChunks, $assembledFilePath);

        $this->writeStatus($uploadId, UploadStatus::TRANSFERRING, 0);

        $this->transferFile(
            $uploadId,
            $assembledFilePath,
            $finalDestination,
            $diskName
        );

        $this->deleteSession($uploadId);
    }

    private function assembleChunks(UploadId $uploadId, int $totalChunks, string $assembledFilePath): void
    {
        $outputHandle = fopen($assembledFilePath, 'wb');
        if ($outputHandle === false) {
            throw new AssembleFailedException('Failed to open output assembled file.');
        }

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkHandle = fopen(
                $this->pathResolver->getAbsolutePath(
                    $this->pathResolver->getChunkPath($uploadId, $i)
                ),
                'rb'
            );
            if ($chunkHandle === false) {
                fclose($outputHandle);
                throw new AssembleFailedException("Failed to open chunk {$i}");
            }
            while (! feof($chunkHandle)) {
                $content = fread($chunkHandle, 8192);
                if ($content !== false) {
                    fwrite($outputHandle, $content);
                }
            }
            fclose($chunkHandle);
            $this->deleteChunk($uploadId, $i);
        }
        fclose($outputHandle);
    }

    private function transferFile(UploadId $uploadId, string $assembledFilePath, Path $finalDestination, string $diskName): void
    {
        $totalSize = filesize($assembledFilePath);

        stream_filter_register('lsm_upload_progress', UploadProgressFilter::class);
        $statusFilePath = $this->pathResolver->getAbsolutePath(
            $this->pathResolver->getStatusPath($uploadId)
        );

        $stream = fopen($assembledFilePath, 'r');
        if ($stream === false) {
            throw new TransferFailedException('Failed to open assembled file for transfer.');
        }

        $filter = stream_filter_append($stream, 'lsm_upload_progress', STREAM_FILTER_READ, [
            UploadProgressFilter::STATUS_FILE_PARAM => $statusFilePath,
            UploadProgressFilter::TOTAL_SIZE_PARAM  => $totalSize,
        ]);

        Storage::disk($diskName)->put($finalDestination->value, $stream);

        if ($filter !== false) {
            stream_filter_remove($filter);
        }
        fclose($stream);
    }
}
