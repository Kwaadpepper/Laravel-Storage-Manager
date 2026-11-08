<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathPropertyFactory;

class FilePropertyExtractor
{
    public function __construct(
        private readonly StorageGateway $storageGateway,
    ) {
    }

    public function fileProperties(Disk $disk, Path $path): PathProperties
    {
        /** @var array{dirname?: string, basename?: string, extension?: string, filename?: string} $pathInfo */
        $pathInfo   = pathinfo($path->value);
        $rawDirname = $pathInfo['dirname'] ?? '.';
        $dirname    = $rawDirname === '.' ? '' : $rawDirname;
        $basename   = $pathInfo['basename']  ?? '';
        $extension  = $pathInfo['extension'] ?? '';
        $filename   = $pathInfo['filename']  ?? '';

        return PathPropertyFactory::fromArray([
            'type'       => 'file',
            'path'       => $path,
            'basename'   => $basename,
            'dirname'    => $dirname,
            'extension'  => $extension,
            'filename'   => $filename,
            'size'       => $this->storageGateway->size($disk, $path->value),
            'timestamp'  => $this->storageGateway->lastModified($disk, $path),
            'visibility' => $this->storageGateway->visibility($disk, $path),
        ]);
    }

    public function directoryProperties(Disk $disk, Path $path): PathProperties
    {
        /** @var array{dirname?: string, basename?: string} $pathInfo */
        $pathInfo   = pathinfo($path->value);
        $rawDirname = $pathInfo['dirname'] ?? '.';
        $dirname    = $rawDirname === '.' ? '' : $rawDirname;
        $basename   = $pathInfo['basename'] ?? '';

        if (! $this->storageGateway->supportsDirectoryMetadata($disk)) {
            $timestamp  = null;
            $visibility = null;
        } else {
            $timestamp  = $this->storageGateway->lastModified($disk, $path);
            $visibility = $this->storageGateway->visibility($disk, $path);
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
