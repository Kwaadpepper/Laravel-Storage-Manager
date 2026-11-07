<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Upload;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Enum\DuplicatePolicy;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Enum\UploadStatus;
use Kwaadpepper\LaravelStorageManager\Event\FileCreated;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\Upload\AssembleFailedException;
use Kwaadpepper\LaravelStorageManager\Exception\Upload\MissingChunkException;
use Kwaadpepper\LaravelStorageManager\Exception\Upload\MissingMetadataException;
use Kwaadpepper\LaravelStorageManager\Exception\Upload\TransferFailedException;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathSanitizer;
use Kwaadpepper\LaravelStorageManager\Lib\Security\MimeTypeChecker;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\Stream\UploadProgressFilter;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadMetadata;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadSessionStatus;

class UploadSessionService
{
    private const string METADATA_FILE = 'metadata.json';

    public function __construct(
        private readonly UploadPathResolver $pathResolver,
        private readonly StorageManagerConfig $config,
        private readonly MimeTypeChecker $mimeTypeChecker,
        private readonly StorageGateway $storageGateway,
        private readonly PathSanitizer $pathSanitizer,
        private readonly EventDispatcher $eventDispatcher,
    ) {
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
        try {
            /** @var array<string, mixed> $data */
            $data = json_decode((string) $disk->get($path), true, 512, JSON_THROW_ON_ERROR);

            return is_array($data) ? UploadSessionStatus::fromArray($data) : null;
        } catch (\JsonException) {
            return null;
        }
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
     * Resolve duplicate policy, assemble chunks, validate binary MIME, transfer to target disk, and emit FileCreated.
     *
     * @throws FileOperationException
     * @throws \RuntimeException
     */
    public function completeUpload(
        UploadId $uploadId,
        Disk $disk,
        Path $destinationDir,
        string $fileName,
        ?DuplicatePolicy $requestedPolicy = null,
        ?callable $onProgress = null,
    ): string {
        $disk->assertWritable();

        if ($this->config->upload->sanitizeFilenames) {
            $fileName = $this->pathSanitizer->sanitizeFileName($fileName);
        }

        $policy             = $requestedPolicy ?? $this->config->upload->duplicatePolicy;
        $desiredDestination = Path::appendTo($destinationDir, $fileName);

        if (! $this->storageGateway->exists($disk, $desiredDestination)) {
            $finalDestination = $desiredDestination;
        } else {
            if ($policy === DuplicatePolicy::ERROR) {
                $this->deleteSession($uploadId);
                FileOperationException::throwWith(FileOperationError::FILE_ALREADY_EXISTS);
            }
            $finalDestination = match ($policy) {
                DuplicatePolicy::OVERWRITE   => $desiredDestination,
                DuplicatePolicy::AUTO_RENAME => $this->storageGateway->resolveAvailablePath($disk, $desiredDestination),
            };
            $fileName = basename($finalDestination->value);
        }

        $this->assembleAndTransfer(
            $uploadId,
            $finalDestination,
            $disk->name,
            $onProgress
        );

        $this->eventDispatcher->dispatch(
            FileCreated::class,
            ['path' => $finalDestination->value]
        );

        return $fileName;
    }

    /**
     * Verify all chunks exist, assemble them, validate extension and binary MIME, stream to final destination, and clean up.
     *
     * @throws FileOperationException
     */
    public function assembleAndTransfer(
        UploadId $uploadId,
        Path $finalDestination,
        string $diskName,
        ?callable $onProgress = null,
    ): void {
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

        $this->assembleChunks($uploadId, $totalChunks, $assembledFilePath, $onProgress);

        $extension = strtolower(pathinfo($finalDestination->value, PATHINFO_EXTENSION));
        if (! $this->config->upload->isExtensionAllowed($extension)) {
            $this->deleteSession($uploadId);
            FileOperationException::throwWith(FileOperationError::FORBIDDEN_FILE_EXTENSION);
        }

        $detectedMime = $this->mimeTypeChecker->detectMimeType($assembledFilePath);
        if (! $this->mimeTypeChecker->isMimeAllowedForExtension($extension, $detectedMime)) {
            $this->deleteSession($uploadId);
            FileOperationException::throwWith(FileOperationError::FORBIDDEN_FILE_MIME);
        }

        $this->writeStatus($uploadId, UploadStatus::TRANSFERRING, 0);

        $this->transferFile(
            $uploadId,
            $assembledFilePath,
            $finalDestination,
            $diskName,
            $onProgress
        );

        $this->deleteSession($uploadId);
    }

    private function assembleChunks(
        UploadId $uploadId,
        int $totalChunks,
        string $assembledFilePath,
        ?callable $onProgress = null,
    ): void {
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

            stream_copy_to_stream($chunkHandle, $outputHandle);
            fclose($chunkHandle);
            $this->deleteChunk($uploadId, $i);

            $progress = (int) (($i + 1) / $totalChunks * 100);
            if ($progress % 5 === 0 || $progress === 100) {
                $this->writeStatus($uploadId, UploadStatus::ASSEMBLING, $progress);
                if ($onProgress !== null) {
                    $onProgress('assembling', $progress);
                }
            }
        }
        fclose($outputHandle);
    }

    private function transferFile(
        UploadId $uploadId,
        string $assembledFilePath,
        Path $finalDestination,
        string $diskName,
        ?callable $onProgress = null,
    ): void {
        $totalSize = filesize($assembledFilePath);

        if (! in_array('lsm_upload_progress', stream_get_filters(), true)) {
            stream_filter_register('lsm_upload_progress', UploadProgressFilter::class);
        }
        $statusFilePath = $this->pathResolver->getAbsolutePath(
            $this->pathResolver->getStatusPath($uploadId)
        );

        $stream = fopen($assembledFilePath, 'r');
        if ($stream === false) {
            throw new TransferFailedException('Failed to open assembled file for transfer.');
        }

        $filterParams = [
            UploadProgressFilter::STATUS_FILE_PARAM => $statusFilePath,
            UploadProgressFilter::TOTAL_SIZE_PARAM  => $totalSize,
        ];
        if ($onProgress !== null) {
            $filterParams[UploadProgressFilter::ON_PROGRESS_PARAM] = $onProgress;
        }

        $filter = stream_filter_append($stream, 'lsm_upload_progress', STREAM_FILTER_READ, $filterParams);

        $this->storageGateway->disk($diskName)->put($finalDestination->toRelative(), $stream);

        if ($filter !== false) {
            stream_filter_remove($filter);
        }
        fclose($stream);

        if ($onProgress !== null) {
            $onProgress('transferring', 100);
        }
    }
}
