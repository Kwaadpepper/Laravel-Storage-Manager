<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryRestored;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryTrashed;
use Kwaadpepper\LaravelStorageManager\Event\FileRestored;
use Kwaadpepper\LaravelStorageManager\Event\FileTrashed;
use Kwaadpepper\LaravelStorageManager\Event\TrashEmptied;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathConflictResolver;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Trash\TrashedItem;
use Psr\Log\LoggerInterface;

class TrashService
{
    public function __construct(
        private readonly StorageManagerConfig $config,
        private readonly PathNormalizer $pathNormalizer,
        private readonly EventDispatcher $eventDispatcher,
        private readonly StorageGateway $storageGateway,
        private readonly PathConflictResolver $pathConflictResolver,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function moveToTrash(Disk $disk, Path $path): TrashedItem
    {
        if (! $this->config->trash->enabled) {
            $this->logger->warning('Rejected moveToTrash: trash is disabled', ['disk' => $disk->name, 'path' => $path->value]);
            FileOperationException::throwWith(FileOperationError::TRASH_DISABLED);
        }

        $disk->assertWritable();

        $normalizedPath = $this->pathNormalizer->normalizePath($path->value);
        if ($normalizedPath === '/') {
            $this->logger->warning('Rejected moveToTrash: cannot trash root path', ['disk' => $disk->name]);
            FileOperationException::throwWith(FileOperationError::INVALID_PATH);
        }

        $isDir = $this->storageGateway->resolveIsDirectory($disk, $path);

        $this->assertNotInTrashFolder($normalizedPath);

        $size      = $this->calculateItemSize($disk, $normalizedPath, $isDir);
        $trashId   = (string) Str::orderedUuid();
        $name      = basename($normalizedPath);
        $dirs      = $this->ensureTrashDirectoriesExist($disk);
        $storedRel = $dirs['files'] . '/' . $trashId . '_' . $name;

        $this->storageGateway->move($disk, $normalizedPath, $storedRel, $isDir);

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
        $this->storageGateway->put($disk, $metaRel, (string) json_encode($trashedItem->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->logger->info('Item moved to trash successfully', [
            'disk'     => $disk->name,
            'path'     => $path->value,
            'trashId'  => $trashId,
            'type'     => $isDir ? 'directory' : 'file',
        ]);

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
        $metaDir = $this->getTrashMetaDir();
        $this->logger->debug('Scanning trash items', ['disk' => $disk->name, 'metaDir' => $metaDir]);

        if (! $this->storageGateway->exists($disk, $this->toPath($metaDir))) {
            return [];
        }

        /** @var list<string> $metaFiles */
        $metaFiles = $this->storageGateway->files($disk, $metaDir);
        $items     = [];

        foreach ($metaFiles as $metaFile) {
            if (! str_ends_with($metaFile, '.json')) {
                continue;
            }

            $content = $this->storageGateway->get($disk, $metaFile);
            if (! is_string($content)) {
                $this->logger->warning('Failed to read trash metadata file', ['disk' => $disk->name, 'metaFile' => $metaFile]);

                continue;
            }

            /** @var mixed $data */
            $data = json_decode($content, true);
            if (! is_array($data)) {
                $this->logger->warning('Corrupted trash metadata JSON', ['disk' => $disk->name, 'metaFile' => $metaFile]);

                continue;
            }

            $trashId = basename($metaFile, '.json');
            $lockRel = $this->getTrashLockPath($trashId);
            if ($this->storageGateway->exists($disk, $this->toPath($lockRel))) {
                $this->logger->debug('Trash item is locked', ['disk' => $disk->name, 'trashId' => $trashId]);
                $lockContent    = $this->storageGateway->get($disk, $lockRel);
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
        $this->logger->debug('Getting trash item', ['disk' => $disk->name, 'trashId' => $trashId]);
        $metaPath = $this->getTrashMetaDir() . '/' . $trashId . '.json';

        if (! $this->storageGateway->exists($disk, $this->toPath($metaPath))) {
            return null;
        }

        $content = $this->storageGateway->get($disk, $metaPath);
        if (! is_string($content)) {
            $this->logger->warning('Failed to read trash item metadata', ['disk' => $disk->name, 'trashId' => $trashId]);

            return null;
        }

        /** @var mixed $data */
        $data = json_decode($content, true);
        if (! is_array($data)) {
            $this->logger->warning('Corrupted metadata JSON for trash item', ['disk' => $disk->name, 'trashId' => $trashId]);

            return null;
        }

        $lockRel = $this->getTrashLockPath($trashId);
        if ($this->storageGateway->exists($disk, $this->toPath($lockRel))) {
            $this->logger->debug('Trash item is locked', ['disk' => $disk->name, 'trashId' => $trashId]);
            $lockContent    = $this->storageGateway->get($disk, $lockRel);
            $data['status'] = is_string($lockContent) && $lockContent !== '' ? $lockContent : 'restoring';
        } else {
            $data['status'] = 'trashed';
        }

        /** @var array<string, mixed> $data */
        return TrashedItem::fromArray($data);
    }

    public function restore(Disk $disk, string $trashId): Path
    {
        $disk->assertWritable();
        $this->logger->info('Starting restore for trash item', ['disk' => $disk->name, 'trashId' => $trashId]);

        $lockRel = $this->getTrashLockPath($trashId);

        if ($this->isItemLocked($disk, $trashId)) {
            $this->logger->warning('Cannot restore: trash item is locked', ['disk' => $disk->name, 'trashId' => $trashId]);
            FileOperationException::throwWith(FileOperationError::TRASH_ITEM_LOCKED);
        }

        $item = $this->getItem($disk, $trashId);
        if ($item === null) {
            $this->logger->warning('Cannot restore: trash item metadata not found', ['disk' => $disk->name, 'trashId' => $trashId]);
            FileOperationException::throwWith(FileOperationError::TRASH_ITEM_NOT_FOUND);
        }

        $storedRel = $this->getTrashFilesDir() . '/' . $trashId . '_' . $item->name;
        $isDir     = $item->type === 'directory';

        $exists = $this->storageGateway->exists($disk, $this->toPath($storedRel));
        if (! $exists) {
            $this->logger->warning('Cannot restore: stored trash payload not found', [
                'disk'      => $disk->name,
                'trashId'   => $trashId,
                'storedRel' => $storedRel,
            ]);
            FileOperationException::throwWith(FileOperationError::TRASH_ITEM_NOT_FOUND);
        }

        $this->storageGateway->put($disk, $lockRel, 'restoring');

        try {
            $originalPath = $this->pathNormalizer->normalizePath($item->originalPath->value);
            $parentDir    = ltrim(dirname($originalPath), '/');

            if ($parentDir !== '' && ! $this->storageGateway->exists($disk, $this->toPath($parentDir))) {
                $this->storageGateway->makeDirectory($disk, $parentDir);
            }

            $targetPath = $this->pathConflictResolver->resolveAvailablePath(
                $disk,
                $this->toPath($originalPath),
                '%s (restored%s)%s'
            );

            if ($targetPath->value !== $originalPath) {
                $this->logger->warning('Restore target conflict, auto-renamed target path', [
                    'disk'         => $disk->name,
                    'trashId'      => $trashId,
                    'originalPath' => $originalPath,
                    'targetPath'   => $targetPath->value,
                ]);
            }

            $this->storageGateway->move($disk, $storedRel, $targetPath->value, $isDir);

            $metaRel = $this->getTrashMetaDir() . '/' . $trashId . '.json';
            $this->storageGateway->delete($disk, $metaRel);

            $this->logger->info('Trash item restored successfully', [
                'disk'       => $disk->name,
                'trashId'    => $trashId,
                'targetPath' => $targetPath->value,
            ]);

            $this->eventDispatcher->dispatch(
                $isDir ? DirectoryRestored::class : FileRestored::class,
                ['path' => $targetPath->value, 'trashId' => $trashId]
            );

            return $targetPath;
        } finally {
            if ($this->storageGateway->exists($disk, $this->toPath($lockRel))) {
                $this->storageGateway->delete($disk, $lockRel);
            }
        }
    }

    public function purge(Disk $disk, string $trashId): void
    {
        $disk->assertWritable();
        $this->logger->info('Starting purge for trash item', ['disk' => $disk->name, 'trashId' => $trashId]);

        $lockRel = $this->getTrashLockPath($trashId);

        if ($this->isItemLocked($disk, $trashId)) {
            $this->logger->warning('Cannot purge: trash item is locked', ['disk' => $disk->name, 'trashId' => $trashId]);
            FileOperationException::throwWith(FileOperationError::TRASH_ITEM_LOCKED);
        }

        $item = $this->getItem($disk, $trashId);
        if ($item === null) {
            $this->logger->warning('Cannot purge: trash item metadata not found', ['disk' => $disk->name, 'trashId' => $trashId]);

            return;
        }

        $this->storageGateway->put($disk, $lockRel, 'purging');

        try {
            $storedRel = $this->getTrashFilesDir() . '/' . $trashId . '_' . $item->name;
            if ($item->type === 'directory') {
                if ($this->storageGateway->isDirectory($disk, $this->toPath($storedRel))) {
                    $this->storageGateway->deleteDirectory($disk, $storedRel);
                }
            } else {
                if ($this->storageGateway->exists($disk, $this->toPath($storedRel))) {
                    $this->storageGateway->delete($disk, $storedRel);
                }
            }

            $metaRel = $this->getTrashMetaDir() . '/' . $trashId . '.json';
            if ($this->storageGateway->exists($disk, $this->toPath($metaRel))) {
                $this->storageGateway->delete($disk, $metaRel);
            }

            $this->logger->info('Trash item purged successfully', ['disk' => $disk->name, 'trashId' => $trashId]);
        } finally {
            if ($this->storageGateway->exists($disk, $this->toPath($lockRel))) {
                $this->storageGateway->delete($disk, $lockRel);
            }
        }
    }

    public function emptyTrash(Disk $disk): int
    {
        $disk->assertWritable();
        $this->logger->info('Starting emptyTrash for disk', ['disk' => $disk->name]);

        $metaDir = $this->getTrashMetaDir();

        if ($this->storageGateway->exists($disk, $this->toPath($metaDir))) {
            /** @var list<string> $metaFiles */
            $metaFiles = $this->storageGateway->files($disk, $metaDir);
            foreach ($metaFiles as $file) {
                if (str_ends_with($file, '.lock')) {
                    $this->logger->warning('Cannot emptyTrash: active trash lock found', ['disk' => $disk->name, 'file' => $file]);
                    FileOperationException::throwWith(FileOperationError::TRASH_ITEM_LOCKED);
                }
            }
        }

        $items = $this->list($disk);
        $count = count($items);

        $filesDir = $this->getTrashFilesDir();

        if ($this->storageGateway->exists($disk, $this->toPath($filesDir))) {
            $this->storageGateway->deleteDirectory($disk, $filesDir);
        }

        if ($this->storageGateway->exists($disk, $this->toPath($metaDir))) {
            $this->storageGateway->deleteDirectory($disk, $metaDir);
        }

        $this->storageGateway->makeDirectory($disk, $filesDir);
        $this->storageGateway->makeDirectory($disk, $metaDir);

        $this->logger->info('Trash emptied successfully', ['disk' => $disk->name, 'itemsCount' => $count]);
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

    private function isItemLocked(Disk $disk, string $trashId): bool
    {
        return $this->storageGateway->exists($disk, $this->toPath($this->getTrashLockPath($trashId)));
    }

    public function pruneExpired(?Disk $disk = null, ?int $retentionDays = null): int
    {
        $days = $retentionDays ?? $this->config->trash->retentionDays;
        if ($days <= 0) {
            return 0;
        }

        $cutoff = CarbonImmutable::now()->subDays($days);
        $disks  = $disk !== null ? [$disk] : array_values($this->config->disks->getDisksMap());
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
        return '/' . trim($this->config->trash->folder, '/');
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
        $filesDir = $this->getTrashFilesDir();
        $metaDir  = $this->getTrashMetaDir();

        if (! $this->storageGateway->exists($disk, $this->toPath($filesDir))) {
            $this->storageGateway->makeDirectory($disk, $filesDir);
        }

        if (! $this->storageGateway->exists($disk, $this->toPath($metaDir))) {
            $this->storageGateway->makeDirectory($disk, $metaDir);
        }

        return [
            'files' => $filesDir,
            'meta'  => $metaDir,
        ];
    }

    private function calculateItemSize(Disk $disk, string $normalizedPath, bool $isDir): int
    {
        $relPath = ltrim($normalizedPath, '/');

        if (! $isDir) {
            return $this->storageGateway->size($disk, $relPath);
        }

        $total = 0;
        /** @var list<string> $allFiles */
        $allFiles = $this->storageGateway->allFiles($disk, $relPath);
        foreach ($allFiles as $file) {
            $total += $this->storageGateway->size($disk, $file);
        }

        return $total;
    }

    private function toPath(string $path): Path
    {
        return new Path('/' . ltrim($path, '/'));
    }
}
