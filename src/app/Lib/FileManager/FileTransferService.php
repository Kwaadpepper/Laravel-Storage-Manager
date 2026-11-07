<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Event\BulkRenamed;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryCopied;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryMoved;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryRenamed;
use Kwaadpepper\LaravelStorageManager\Event\FileCopied;
use Kwaadpepper\LaravelStorageManager\Event\FileMoved;
use Kwaadpepper\LaravelStorageManager\Event\FileRenamed;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\Security\MimeTypeChecker;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Psr\Log\LoggerInterface;

readonly class FileTransferService
{
    private const int MIME_SAMPLE_BYTES = 1048576; // 1 MB

    public function __construct(
        private PathNormalizer $pathNormalizer,
        private StorageGateway $storageGateway,
        private PathConflictResolver $pathConflictResolver,
        private MimeTypeChecker $mimeTypeChecker,
        private StorageManagerConfig $config,
        private EventDispatcher $eventDispatcher,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws FileOperationException
     */
    public function rename(Disk $disk, Path $source, string $newName): Path
    {
        $disk->assertWritable();

        $normalizedSource = $this->pathNormalizer->normalizePath($source->value);
        $sourceIsDir      = $this->storageGateway->resolveIsDirectory($disk, $source);

        $destination = Path::appendTo(
            new Path(dirname($normalizedSource)),
            $this->pathNormalizer->normalizePath($newName)
        );

        if (! $sourceIsDir) {
            $this->validateFileRenameTarget($disk, $source, $destination);
        }

        if ($this->storageGateway->exists($disk, $destination)) {
            $this->logger->warning('Rejected rename: destination already exists', [
                'disk'        => $disk->name,
                'source'      => $source->value,
                'destination' => $destination->value,
            ]);
            FileOperationException::throwWith(
                $sourceIsDir
                    ? FileOperationError::DIRECTORY_ALREADY_EXISTS
                    : FileOperationError::FILE_ALREADY_EXISTS
            );
        }

        $this->storageGateway->move($disk, $normalizedSource, $destination->value, $sourceIsDir);

        $this->logger->info('Item renamed successfully', [
            'disk'        => $disk->name,
            'source'      => $source->value,
            'destination' => $destination->value,
            'isDir'       => $sourceIsDir,
        ]);

        $this->eventDispatcher->dispatch(
            $sourceIsDir ? DirectoryRenamed::class : FileRenamed::class,
            [
                'old_path' => $source->value,
                'new_path' => $destination->value,
            ]
        );

        return $destination;
    }

    /**
     * @throws FileOperationException
     */
    public function copy(Disk $disk, Path $source, Path $destinationDir): Path
    {
        $disk->assertWritable();

        $normalizedSource = $this->pathNormalizer->normalizePath($source->value);
        $sourceIsDir      = $this->storageGateway->resolveIsDirectory($disk, $source);

        $basename    = basename($normalizedSource);
        $desired     = Path::appendTo($destinationDir, $this->pathNormalizer->normalizePath($basename));
        $destination = $this->pathConflictResolver->resolveAvailablePath($disk, $desired, '%s (copy%s)%s');

        if ($destination->value !== $desired->value) {
            $this->logger->warning('Duplicate collision on copy, resolved to new path', [
                'disk'        => $disk->name,
                'desired'     => $desired->value,
                'destination' => $destination->value,
            ]);
        }

        $this->storageGateway->copy($disk, $source, $destination, $sourceIsDir);

        $this->logger->info('Item copied successfully', [
            'disk'        => $disk->name,
            'source'      => $source->value,
            'destination' => $destination->value,
            'isDir'       => $sourceIsDir,
        ]);

        $this->eventDispatcher->dispatch(
            $sourceIsDir ? DirectoryCopied::class : FileCopied::class,
            [
                'source_path' => $source->value,
                'target_path' => $destination->value,
            ]
        );

        return $destination;
    }

    /**
     * @throws FileOperationException
     */
    public function move(Disk $disk, Path $source, Path $destinationDir): Path
    {
        $disk->assertWritable();

        $normalizedSource = $this->pathNormalizer->normalizePath($source->value);
        $sourceIsDir      = $this->storageGateway->resolveIsDirectory($disk, $source);

        $basename    = basename($normalizedSource);
        $desired     = Path::appendTo($destinationDir, $this->pathNormalizer->normalizePath($basename));
        $destination = $this->pathConflictResolver->resolveAvailablePath($disk, $desired, '%s (copy%s)%s');

        if ($destination->value !== $desired->value) {
            $this->logger->warning('Duplicate collision on move, resolved to new path', [
                'disk'        => $disk->name,
                'desired'     => $desired->value,
                'destination' => $destination->value,
            ]);
        }

        $this->storageGateway->move($disk, $normalizedSource, $destination->value, $sourceIsDir);

        $this->logger->info('Item moved successfully', [
            'disk'        => $disk->name,
            'source'      => $source->value,
            'destination' => $destination->value,
            'isDir'       => $sourceIsDir,
        ]);

        $this->eventDispatcher->dispatch(
            $sourceIsDir ? DirectoryMoved::class : FileMoved::class,
            [
                'source_path' => $source->value,
                'target_path' => $destination->value,
            ]
        );

        return $destination;
    }

    /**
     * @param  array<int, array{from: Path, to: string}>  $renames
     * @return array<int, array{from: string, to: string}>
     *
     * @throws FileOperationException
     */
    public function bulkRename(Disk $disk, array $renames): array
    {
        $disk->assertWritable();
        $this->logger->info('Starting bulk rename', ['disk' => $disk->name, 'count' => count($renames)]);

        $plan = $this->buildBulkRenamePlan($disk, $renames);

        $sourcePaths = array_map(
            fn (array $p): string => $this->pathNormalizer->normalizePath($p['from']->value),
            $plan
        );
        $hasOverlap = false;
        foreach ($plan as $p) {
            $normTarget = $this->pathNormalizer->normalizePath($p['target']->value);
            if (in_array($normTarget, $sourcePaths, true)) {
                $hasOverlap = true;
                break;
            }
        }

        $result = $this->executeBulkRename($disk, $plan, $hasOverlap);

        $this->logger->info('Bulk rename completed successfully', ['disk' => $disk->name, 'count' => count($result)]);
        $this->eventDispatcher->dispatch(
            BulkRenamed::class,
            ['renamed' => $result]
        );

        return $result;
    }

    /**
     * @throws FileOperationException
     */
    private function validateFileRenameTarget(Disk $disk, Path $source, Path $destination): void
    {
        $oldExt = strtolower(pathinfo($source->value, PATHINFO_EXTENSION));
        $newExt = strtolower(pathinfo($destination->value, PATHINFO_EXTENSION));

        if (! $this->config->upload->isExtensionAllowed($newExt)) {
            $this->logger->warning('Rejected validateFileRenameTarget: forbidden extension', [
                'disk'        => $disk->name,
                'source'      => $source->value,
                'destination' => $destination->value,
                'extension'   => $newExt,
            ]);
            FileOperationException::throwWith(FileOperationError::FORBIDDEN_FILE_EXTENSION);
        }

        if ($oldExt === $newExt) {
            return;
        }

        $sample = $this->storageGateway->readStream($disk, $source)->getContents(self::MIME_SAMPLE_BYTES);

        if ($sample !== '') {
            $detectedMime = $this->mimeTypeChecker->detectBufferMimeType($sample);
            if (! $this->mimeTypeChecker->isMimeAllowedForExtension($newExt, $detectedMime)) {
                $this->logger->warning('Rejected validateFileRenameTarget: forbidden mime type for extension', [
                    'disk'        => $disk->name,
                    'source'      => $source->value,
                    'destination' => $destination->value,
                    'extension'   => $newExt,
                    'mime'        => $detectedMime,
                ]);
                FileOperationException::throwWith(FileOperationError::FORBIDDEN_FILE_MIME);
            }
        }
    }

    /**
     * @param  array<int, array{from: Path, to: string}>  $renames
     * @return array<int, array{from: Path, toName: string, target: Path, isDir: bool}>
     *
     * @throws FileOperationException
     */
    private function buildBulkRenamePlan(Disk $disk, array $renames): array
    {
        $plan = [];

        foreach ($renames as $rename) {
            $from           = $rename['from'];
            $toName         = $rename['to'];
            $normalizedFrom = $this->pathNormalizer->normalizePath($from->value);
            $isDir          = $this->storageGateway->resolveIsDirectory($disk, $from);

            $target = Path::appendTo(
                new Path(dirname($normalizedFrom)),
                $this->pathNormalizer->normalizePath($toName)
            );

            if (! $isDir) {
                $this->validateFileRenameTarget($disk, $from, $target);
            }

            $plan[] = [
                'from'   => $from,
                'toName' => $toName,
                'target' => $target,
                'isDir'  => $isDir,
            ];
        }

        $targetPaths = array_map(fn (array $p): string => (string) $p['target'], $plan);
        if (count($targetPaths) !== count(array_unique($targetPaths))) {
            $this->logger->warning('Rejected buildBulkRenamePlan: duplicate target path in batch', ['disk' => $disk->name]);
            FileOperationException::throwWith(FileOperationError::FILE_ALREADY_EXISTS);
        }

        $sourcePaths = array_map(
            fn (array $p): string => $this->pathNormalizer->normalizePath($p['from']->value),
            $plan
        );
        foreach ($plan as $p) {
            $normTarget = $this->pathNormalizer->normalizePath($p['target']->value);
            if (! in_array($normTarget, $sourcePaths, true) && $this->storageGateway->exists($disk, $p['target'])) {
                $this->logger->warning('Rejected buildBulkRenamePlan: target already exists on disk', [
                    'disk'   => $disk->name,
                    'target' => $p['target']->value,
                ]);
                FileOperationException::throwWith(
                    $p['isDir']
                        ? FileOperationError::DIRECTORY_ALREADY_EXISTS
                        : FileOperationError::FILE_ALREADY_EXISTS
                );
            }
        }

        return $plan;
    }

    /**
     * @param  array<int, array{from: Path, toName: string, target: Path, isDir: bool}>  $plan
     * @return array<int, array{from: string, to: string}>
     */
    private function executeBulkRename(Disk $disk, array $plan, bool $hasOverlap): array
    {
        $result = [];

        if ($hasOverlap) {
            $tempPlan = [];
            foreach ($plan as $idx => $p) {
                $tempName   = sprintf('.tmp_bulk_%s_%d', uniqid(), $idx);
                $tempTarget = Path::appendTo(
                    new Path(dirname($this->pathNormalizer->normalizePath($p['from']->value))),
                    $tempName
                );
                $this->storageGateway->move(
                    $disk,
                    $p['from']->value,
                    $tempTarget->value,
                    $p['isDir']
                );
                $tempPlan[] = [
                    'temp'   => $tempTarget,
                    'target' => $p['target'],
                    'from'   => $p['from'],
                    'isDir'  => $p['isDir'],
                ];
            }

            foreach ($tempPlan as $tp) {
                $this->storageGateway->move(
                    $disk,
                    $tp['temp']->value,
                    $tp['target']->value,
                    $tp['isDir']
                );
                $this->eventDispatcher->dispatch(
                    $tp['isDir'] ? DirectoryRenamed::class : FileRenamed::class,
                    [
                        'old_path' => (string) $tp['from'],
                        'new_path' => (string) $tp['target'],
                    ]
                );
                $result[] = [
                    'from' => (string) $tp['from'],
                    'to'   => (string) $tp['target'],
                ];
            }
        } else {
            foreach ($plan as $p) {
                $this->rename($disk, $p['from'], $p['toName']);
                $result[] = [
                    'from' => (string) $p['from'],
                    'to'   => (string) $p['target'],
                ];
            }
        }

        return $result;
    }
}
