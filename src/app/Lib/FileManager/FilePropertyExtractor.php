<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Illuminate\Filesystem\AwsS3V3Adapter;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathPropertyFactory;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\PhpseclibV3\SftpAdapter;

class FilePropertyExtractor
{
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

        return PathPropertyFactory::fromArray([
            'type'       => 'file',
            'path'       => $path,
            'basename'   => $basename,
            'dirname'    => $dirname,
            'extension'  => $extension,
            'filename'   => $filename,
            'size'       => $storageDisk->size($pathValue),
            'timestamp'  => $storageDisk->lastModified($pathValue),
            'visibility' => $storageDisk->getVisibility($pathValue),
        ]);
    }

    public function directoryProperties(Disk $disk, Path $path): PathProperties
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storageDisk */
        $storageDisk   = $disk->getStorageDisk();
        $leagueAdpater = $storageDisk->getAdapter();
        $pathValue     = $path->value;
        /** @var array<string,string> $pathInfo */
        $pathInfo = pathinfo($pathValue);
        $dirname  = $pathInfo['dirname'] === '.' ? '' : $pathInfo['dirname'];
        $basename = $pathInfo['basename'];

        if (
            $leagueAdpater instanceof AwsS3V3Adapter
            || $leagueAdpater instanceof FtpAdapter
            || $leagueAdpater instanceof SftpAdapter
        ) {
            $timestamp  = null;
            $visibility = null;
        } else {
            $timestamp  = $storageDisk->lastModified($pathValue);
            $visibility = $storageDisk->getVisibility($pathValue);
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
