<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Illuminate\Filesystem\AwsS3V3Adapter;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathPropertyFactory;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\PhpseclibV3\SftpAdapter;

class FilePropertyExtractor
{
    public function fileProperties(Disk $disk, string $path): PathProperties
    {
        $pathInfo    = pathinfo($path);
        $storageDisk = $disk->getStorageDisk();

        return PathPropertyFactory::fromArray([
            'type'       => 'file',
            'path'       => $path,
            'basename'   => $pathInfo['basename'],
            'dirname'    => $pathInfo['dirname'] === '.' ? '' : $pathInfo['dirname'],
            'extension'  => $pathInfo['extension'] ?? '',
            'filename'   => $pathInfo['filename'],
            'size'       => $storageDisk->size($path),
            'timestamp'  => $storageDisk->lastModified($path),
            'visibility' => $storageDisk->getVisibility($path),
        ]);
    }

    public function directoryProperties(Disk $disk, string $path): PathProperties
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storageDisk */
        $storageDisk   = $disk->getStorageDisk();
        $leagueAdpater = $storageDisk->getAdapter();
        $pathInfo      = pathinfo($path);

        if (
            $leagueAdpater instanceof AwsS3V3Adapter
            || $leagueAdpater instanceof FtpAdapter
            || $leagueAdpater instanceof SftpAdapter
        ) {
            $timestamp  = null;
            $visibility = null;
        } else {
            $timestamp  = $storageDisk->lastModified($path);
            $visibility = $storageDisk->getVisibility($path);
        }

        return PathPropertyFactory::fromArray([
            'type'       => 'dir',
            'path'       => $path,
            'basename'   => $pathInfo['basename'],
            'dirname'    => $pathInfo['dirname'] === '.' ? '' : $pathInfo['dirname'],
            'timestamp'  => $timestamp,
            'visibility' => $visibility,
        ]);
    }
}
