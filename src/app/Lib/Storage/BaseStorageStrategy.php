<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\FileAttributes;

abstract readonly class BaseStorageStrategy implements StorageDriverStrategy
{
    protected function computeFlysystemUsedBytes(Filesystem $storage): int
    {
        $totalBytes = 0;

        try {
            if ($storage instanceof FilesystemAdapter) {
                foreach ($storage->getDriver()->listContents('', true) as $item) {
                    if ($item->isFile() && $item instanceof FileAttributes) {
                        $totalBytes += $item->fileSize() ?? 0;
                    }
                }

                return $totalBytes;
            }

            /** @var list<string> $files */
            $files = $storage->allFiles();
            foreach ($files as $file) {
                try {
                    $totalBytes += $storage->size($file);
                } catch (\Throwable $fileEx) {
                    unset($fileEx);
                }
            }
        } catch (\Throwable $listEx) {
            unset($listEx);
        }

        return $totalBytes;
    }
}
