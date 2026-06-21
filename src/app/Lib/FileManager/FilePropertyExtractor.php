<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathPropertyFactory;

class FilePropertyExtractor
{
    private const DRIVERS_WITHOUT_DIR_METADATA = ['s3', 'ftp'];

    public function fileProperties(Disk $disk, Path $path): PathProperties
    {
        $pathValue   = $path->value;
        $storageDisk = $disk->getStorageDisk();
        /** @var array<string,string> $pathInfo */
        $pathInfo    = pathinfo($pathValue);

        $dirname   = $pathInfo['dirname'] === '.' ? '' : $pathInfo['dirname'];
        $basename  = $pathInfo['basename'];
        $extension = $pathInfo['extension'] ?? '';
        $filename  = $pathInfo['filename'];

        try {
            $timestamp = $storageDisk->lastModified($pathValue);
        } catch (\Throwable) {
            $timestamp = null;
        }

        try {
            $visibility = $storageDisk->getVisibility($pathValue);
        } catch (\Throwable) {
            $visibility = null;
        }

        return PathPropertyFactory::fromArray([
            'type'       => 'file',
            'path'       => $path,
            'basename'   => $basename,
            'dirname'    => $dirname,
            'extension'  => $extension,
            'filename'   => $filename,
            'size'       => $storageDisk->size($pathValue),
            'timestamp'  => $timestamp,
            'visibility' => $visibility,
        ]);
    }

    public function directoryProperties(Disk $disk, Path $path): PathProperties
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storageDisk */
        $storageDisk = $disk->getStorageDisk();
        $pathValue   = $path->value;
        /** @var array<string,string> $pathInfo */
        $pathInfo = pathinfo($pathValue);
        $dirname  = $pathInfo['dirname'] === '.' ? '' : $pathInfo['dirname'];
        $basename = $pathInfo['basename'];

        if (in_array($disk->driver, self::DRIVERS_WITHOUT_DIR_METADATA, true)) {
            $timestamp  = null;
            $visibility = null;
        } else {
            try {
                $timestamp = $storageDisk->lastModified($pathValue);
            } catch (\Throwable) {
                $timestamp = null;
            }
            try {
                $visibility = $storageDisk->getVisibility($pathValue);
            } catch (\Throwable) {
                $visibility = null;
            }
        }

        return PathPropertyFactory::fromArray([
            'type'       => 'dir',
            'path'       => $path,
            'basename'   => $basename,
            'dirname'    => $dirname,
            'timestamp'  => $timestamp,
            'visibility' => $visibility,
        ]);
    }
}
