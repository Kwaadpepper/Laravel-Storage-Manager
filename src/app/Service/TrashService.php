<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryRestored;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryTrashed;
use Kwaadpepper\LaravelStorageManager\Event\FileRestored;
use Kwaadpepper\LaravelStorageManager\Event\FileTrashed;
use Kwaadpepper\LaravelStorageManager\Event\TrashEmptied;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileManager;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Trash\TrashedItem;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;

class TrashService
{
    public function __construct(
        private readonly ConfigRepository $configRepository,
        private readonly PathNormalizer $pathNormalizer,
        private readonly EventDispatcher $eventDispatcher,
        private readonly FileManager $fileManager,
    ) {
    }

    public function moveToTrash(Disk $disk, Path $path): TrashedItem
    {
        if (! $this->configRepository->isTrashEnabled()) {
            FileOperationException::throwWith(FileOperationError::TRASH_DISABLED);
        }

        $normalizedPath = $this->pathNormalizer->normalizePath($path->value);
        if ($normalizedPath === '/') {
            FileOperationException::throwWith(FileOperationError::INVALID_PATH);
        }

        $this->fileManager->setActiveDisk($disk);
        $isDir  = $this->fileManager->isDirectory($path);
        $isFile = $this->fileManager->isFile($path);

        if (! $isDir && ! $isFile) {
            FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
        }

        $this->assertNotInTrashFolder($normalizedPath);

        $storage   = Storage::disk($disk->name);
        $size      = $this->calculateItemSize($disk, $normalizedPath, $isDir);
        $trashId   = (string) Str::orderedUuid();
        $name      = basename($normalizedPath);
        $dirs      = $this->ensureTrashDirectoriesExist($disk);
        $storedRel = $dirs['files'] . '/' . $trashId . '_' . $name;

        $this->moveItem($disk, $normalizedPath, $storedRel, $isDir);

        $trashedItem = new TrashedItem(
            trashId: $trashId,
            originalPath: $path,
            name: $name,
            type: $isDir ? 'directory' : 'file',
            size: $size,
            deletedAt: CarbonImmutable::now(),
            diskName: $disk->name,
        );

        $metaRel = $dirs['meta'] . '/' . $trashId . '.json';
        $storage->put($metaRel, (string) json_encode($trashedItem->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->eventDispatcher->dispatch(
            $isDir ? DirectoryTrashed::class : FileTrashed::class,
            ['path' => $path->value, 'trashId' => $trashId]
        );

        return $trashedItem;
    }

    /**
     * @return list<TrashedItem>
     */
    public function list(Disk $disk): array
    {
        $storage = Storage::disk($disk->name);
        $metaDir = $this->getTrashMetaDir();

        if (! $storage->exists($metaDir) && ! $storage->directoryExists($metaDir)) {
            return [];
        }

        $metaFiles = $storage->files($metaDir);
        $items     = [];

        foreach ($metaFiles as $metaFile) {
            if (! str_ends_with($metaFile, '.json')) {
                continue;
            }

            $content = $storage->get($metaFile);
            if (! is_string($content)) {
                continue;
            }

            /** @var mixed $data */
            $data = json_decode($content, true);
            if (! is_array($data)) {
                continue;
            }

            $trashId = basename($metaFile, '.json');
            $lockRel = $this->getTrashLockPath($trashId);
            if ($storage->exists($lockRel)) {
                $lockContent    = $storage->get($lockRel);
                $data['status'] = is_string($lockContent) && $lockContent !== '' ? $lockContent : 'restoring';
            } else {
                $data['status'] = 'trashed';
            }

            /** @var array<string, mixed> $data */
            $items[] = TrashedItem::fromArray($data);
        }

        usort(
            $items,
            static function (TrashedItem $a, TrashedItem $b): int {
                $cmp = $b->deletedAt <=> $a->deletedAt;

                return $cmp !== 0 ? $cmp : strcmp($b->trashId, $a->trashId);
            }
        );

        return $items;
    }

    public function getItem(Disk $disk, string $trashId): ?TrashedItem
    {
        $storage  = Storage::disk($disk->name);
        $metaPath = $this->getTrashMetaDir() . '/' . $trashId . '.json';

        if (! $storage->exists($metaPath)) {
            return null;
        }

        $content = $storage->get($metaPath);
        if (! is_string($content)) {
            return null;
        }

        /** @var mixed $data */
        $data = json_decode($content, true);
        if (! is_array($data)) {
            return null;
        }

        $lockRel = $this->getTrashLockPath($trashId);
        if ($storage->exists($lockRel)) {
            $lockContent    = $storage->get($lockRel);
            $data['status'] = is_string($lockContent) && $lockContent !== '' ? $lockContent : 'restoring';
        } else {
            $data['status'] = 'trashed';
        }

        /** @var array<string, mixed> $data */
        return TrashedItem::fromArray($data);
    }

    public function restore(Disk $disk, string $trashId): Path
    {
        $storage = Storage::disk($disk->name);
        $lockRel = $this->getTrashLockPath($trashId);

        if ($this->isItemLocked($storage, $trashId)) {
            FileOperationException::throwWith(FileOperationError::TRASH_ITEM_LOCKED);
        }

        $item = $this->getItem($disk, $trashId);
        if ($item === null) {
            FileOperationException::throwWith(FileOperationError::TRASH_ITEM_NOT_FOUND);
        }

        $storedRel = $this->getTrashFilesDir() . '/' . $trashId . '_' . $item->name;
        $isDir     = $item->type === 'directory';

        $exists = $isDir ? $storage->directoryExists($storedRel) : $storage->exists($storedRel);
        if (! $exists) {
            FileOperationException::throwWith(FileOperationError::TRASH_ITEM_NOT_FOUND);
        }

        $storage->put($lockRel, 'restoring');

        try {
            $originalPath = $this->pathNormalizer->normalizePath($item->originalPath->value);
            $parentDir    = ltrim(dirname($originalPath), '/');

            if ($parentDir !== '' && ! $storage->directoryExists($parentDir) && ! $storage->exists($parentDir)) {
                $storage->makeDirectory($parentDir);
            }

            $targetPath = $this->resolveAvailableDestination($disk, $originalPath);

            $this->moveItem($disk, $storedRel, $targetPath, $isDir);

            $metaRel = $this->getTrashMetaDir() . '/' . $trashId . '.json';
            $storage->delete($metaRel);

            $this->eventDispatcher->dispatch(
                $isDir ? DirectoryRestored::class : FileRestored::class,
                ['path' => $targetPath, 'trashId' => $trashId]
            );

            return new Path($targetPath);
        } finally {
            if ($storage->exists($lockRel)) {
                $storage->delete($lockRel);
            }
        }
    }

    public function purge(Disk $disk, string $trashId): void
    {
        $storage = Storage::disk($disk->name);
        $lockRel = $this->getTrashLockPath($trashId);

        if ($this->isItemLocked($storage, $trashId)) {
            FileOperationException::throwWith(FileOperationError::TRASH_ITEM_LOCKED);
        }

        $item = $this->getItem($disk, $trashId);
        if ($item === null) {
            return;
        }

        $storage->put($lockRel, 'purging');

        try {
            $storedRel = $this->getTrashFilesDir() . '/' . $trashId . '_' . $item->name;
            if ($item->type === 'directory') {
                if ($storage->directoryExists($storedRel)) {
                    $storage->deleteDirectory($storedRel);
                }
            } else {
                if ($storage->exists($storedRel)) {
                    $storage->delete($storedRel);
                }
            }

            $metaRel = $this->getTrashMetaDir() . '/' . $trashId . '.json';
            if ($storage->exists($metaRel)) {
                $storage->delete($metaRel);
            }
        } finally {
            if ($storage->exists($lockRel)) {
                $storage->delete($lockRel);
            }
        }
    }

    public function emptyTrash(Disk $disk): int
    {
        $storage = Storage::disk($disk->name);
        $metaDir = $this->getTrashMetaDir();

        if ($storage->exists($metaDir) || $storage->directoryExists($metaDir)) {
            foreach ($storage->files($metaDir) as $file) {
                if (str_ends_with($file, '.lock')) {
                    FileOperationException::throwWith(FileOperationError::TRASH_ITEM_LOCKED);
                }
            }
        }

        $items = $this->list($disk);
        $count = count($items);

        $filesDir = $this->getTrashFilesDir();

        if ($storage->directoryExists($filesDir) || $storage->exists($filesDir)) {
            $storage->deleteDirectory($filesDir);
        }

        if ($storage->directoryExists($metaDir) || $storage->exists($metaDir)) {
            $storage->deleteDirectory($metaDir);
        }

        $storage->makeDirectory($filesDir);
        $storage->makeDirectory($metaDir);

        $this->eventDispatcher->dispatch(
            TrashEmptied::class,
            ['disk' => $disk->name, 'count' => $count]
        );

        return $count;
    }

    private function getTrashLockPath(string $trashId): string
    {
        return $this->getTrashMetaDir() . '/' . $trashId . '.lock';
    }

    private function isItemLocked(Filesystem $storage, string $trashId): bool
    {
        return $storage->exists($this->getTrashLockPath($trashId));
    }

    public function pruneExpired(?Disk $disk = null, ?int $retentionDays = null): int
    {
        $days = $retentionDays ?? $this->configRepository->getTrashRetentionDays();
        if ($days <= 0) {
            return 0;
        }

        $cutoff = CarbonImmutable::now()->subDays($days);
        $disks  = $disk !== null ? [$disk] : array_values($this->configRepository->getDisksMap());
        $purged = 0;

        foreach ($disks as $d) {
            $items = $this->list($d);
            foreach ($items as $item) {
                if ($item->deletedAt->lessThan($cutoff)) {
                    $this->purge($d, $item->trashId);
                    $purged++;
                }
            }
        }

        return $purged;
    }

    public function getTrashBaseFolder(): string
    {
        return '/' . trim($this->configRepository->getTrashFolder(), '/');
    }

    public function getTrashFilesDir(): string
    {
        return trim($this->getTrashBaseFolder() . '/files', '/');
    }

    public function getTrashMetaDir(): string
    {
        return trim($this->getTrashBaseFolder() . '/meta', '/');
    }

    private function assertNotInTrashFolder(string $normalizedPath): void
    {
        $trashFolder = $this->getTrashBaseFolder();
        if ($normalizedPath === $trashFolder || str_starts_with($normalizedPath, $trashFolder . '/')) {
            FileOperationException::throwWith(FileOperationError::INVALID_PATH);
        }
    }

    /**
     * @return array{files: string, meta: string}
     */
    private function ensureTrashDirectoriesExist(Disk $disk): array
    {
        $storage  = Storage::disk($disk->name);
        $filesDir = $this->getTrashFilesDir();
        $metaDir  = $this->getTrashMetaDir();

        if (! $storage->exists($filesDir) && ! $storage->directoryExists($filesDir)) {
            $storage->makeDirectory($filesDir);
        }

        if (! $storage->exists($metaDir) && ! $storage->directoryExists($metaDir)) {
            $storage->makeDirectory($metaDir);
        }

        return [
            'files' => $filesDir,
            'meta'  => $metaDir,
        ];
    }

    private function calculateItemSize(Disk $disk, string $normalizedPath, bool $isDir): int
    {
        $storage = Storage::disk($disk->name);
        $relPath = ltrim($normalizedPath, '/');

        if (! $isDir) {
            return $storage->size($relPath);
        }

        $total = 0;
        foreach ($storage->allFiles($relPath) as $file) {
            $total += $storage->size($file);
        }

        return $total;
    }

    private function resolveAvailableDestination(Disk $disk, string $originalPath): string
    {
        $storage    = Storage::disk($disk->name);
        $targetPath = $originalPath;
        $counter    = 1;
        $parentDir  = dirname($originalPath);

        while ($storage->exists(ltrim($targetPath, '/')) || $storage->directoryExists(ltrim($targetPath, '/'))) {
            $pathInfo   = pathinfo($originalPath);
            $ext        = isset($pathInfo['extension']) && $pathInfo['extension'] !== '' ? '.' . $pathInfo['extension'] : '';
            $filename   = $pathInfo['filename'];
            $suffix     = $counter > 1 ? " {$counter}" : '';
            $newName    = sprintf('%s (restored%s)%s', $filename, $suffix, $ext);
            $targetPath = $this->pathNormalizer->normalizePath($parentDir . '/' . $newName);
            $counter++;
        }

        return $targetPath;
    }

    private function moveItem(Disk $disk, string $source, string $destination, bool $isDir): void
    {
        $storage = Storage::disk($disk->name);
        $srcRel  = ltrim($source, '/');
        $destRel = ltrim($destination, '/');

        if ($isDir) {
            if (in_array($disk->driver, ['s3', 'ftp', 'sftp'], true)) {
                $this->polyfillMoveDirectory($disk, $srcRel, $destRel);
            } else {
                $moved = false;
                try {
                    $moved = $storage->move($srcRel, $destRel);
                } catch (\Throwable) {
                    $moved = false;
                }

                if (! $moved) {
                    $this->polyfillMoveDirectory($disk, $srcRel, $destRel);
                }
            }
        } else {
            if ($storage instanceof AwsS3V3Adapter) {
                $this->polyfillMoveFile($storage, $srcRel, $destRel);
            } else {
                $moved = false;
                try {
                    $moved = $storage->move($srcRel, $destRel);
                } catch (\Throwable) {
                    $moved = false;
                }

                if (! $moved) {
                    $this->polyfillMoveFile($storage, $srcRel, $destRel);
                }
            }
        }
    }

    private function polyfillMoveFile(Filesystem $storage, string $source, string $destination): void
    {
        $copied = false;

        if ($storage instanceof AwsS3V3Adapter) {
            $copied = $this->attemptS3ServerSideCopy($storage, $source, $destination);
        }

        if (! $copied) {
            $copied = $this->attemptCopy($storage, $source, $destination);
        }

        if (! $copied) {
            $copied = $this->attemptStreamCopy($storage, $source, $destination);
        }

        if (! $copied) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

        $this->deleteSourceFile($storage, $source);
    }

    private function attemptCopy(Filesystem $storage, string $source, string $destination): bool
    {
        try {
            return $storage->copy($source, $destination);
        } catch (\Throwable) {
            return false;
        }
    }

    private function attemptS3ServerSideCopy(AwsS3V3Adapter $storage, string $source, string $destination): bool
    {
        $bucket  = (string) ($storage->getConfig()['bucket'] ?? '');
        $srcKey  = $storage->path($source);
        $destKey = $storage->path($destination);

        $encodedKey = implode('/', array_map('rawurlencode', explode('/', ltrim($srcKey, '/'))));

        try {
            $storage->getClient()->copyObject([
                'Bucket'     => $bucket,
                'Key'        => $destKey,
                'CopySource' => '/' . $bucket . '/' . $encodedKey,
            ]);

            return true;
        } catch (\Throwable) {
            try {
                $storage->getClient()->copyObject([
                    'Bucket'     => $bucket,
                    'Key'        => $destKey,
                    'CopySource' => $bucket . '/' . $encodedKey,
                ]);

                return true;
            } catch (\Throwable $e2) {
                Log::warning(sprintf('S3 server-side copy failed for %s to %s: %s', $source, $destination, $e2->getMessage()));

                return false;
            }
        }
    }

    private function attemptStreamCopy(Filesystem $storage, string $source, string $destination): bool
    {
        try {
            $stream = $storage->readStream($source);
            if ($stream === false || $stream === null) {
                return false;
            }

            $written = $storage->writeStream($destination, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            return $written !== false;
        } catch (\Throwable) {
            return false;
        }
    }

    private function deleteSourceFile(Filesystem $storage, string $source): void
    {
        try {
            $deleted = $storage->delete($source);
        } catch (\Throwable) {
            $deleted = false;
        }

        if (! $deleted) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }
    }

    private function polyfillMoveDirectory(Disk $disk, string $source, string $destination): void
    {
        $storage = Storage::disk($disk->name);
        if ($storage->makeDirectory($destination) === false) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

        $directories = $storage->allDirectories($source);
        foreach ($directories as $dir) {
            $normalizedDir = $this->pathNormalizer->normalizePath($dir);
            $relativePath  = substr($normalizedDir, strlen('/' . $source) + 1);
            $storage->makeDirectory(ltrim($destination . '/' . $relativePath, '/'));
        }

        $files = $storage->allFiles($source);
        foreach ($files as $file) {
            $normalizedFile = $this->pathNormalizer->normalizePath($file);
            $relativePath   = substr($normalizedFile, strlen('/' . $source) + 1);
            $this->polyfillMoveFile($storage, $file, ltrim($destination . '/' . $relativePath, '/'));
        }

        $storage->deleteDirectory($source);
    }
}
