<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Event\ArchiveExtracted;
use Kwaadpepper\LaravelStorageManager\Event\ZipCreated;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\Security\MimeTypeChecker;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\FileStream;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

readonly class ArchiveService
{
    private const int MIME_SAMPLE_BYTES = 1048576; // 1 MB

    public function __construct(
        private PathNormalizer $pathNormalizer,
        private StorageGateway $storageGateway,
        private StorageManagerConfig $config,
        private MimeTypeChecker $mimeTypeChecker,
        private EventDispatcher $eventDispatcher,
    ) {
    }

    public function supportsZip(): bool
    {
        return extension_loaded('zip') && class_exists(\ZipArchive::class);
    }

    /**
     * @param  Path[]  $paths
     *
     * @throws FileOperationException
     */
    public function zip(Disk $disk, array $paths, Path $destinationZip): Path
    {
        $this->assertCanZip($disk, $paths, $destinationZip);

        $tempZip = tempnam(sys_get_temp_dir(), 'sm_zip_');
        if ($tempZip === false) {
            FileOperationException::throwWith(FileOperationError::ARCHIVE_OPERATION_FAILED);
        }

        try {
            $zip = new \ZipArchive();
            $res = $zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            if ($res !== true) {
                FileOperationException::throwWith(FileOperationError::ARCHIVE_OPERATION_FAILED);
            }

            foreach ($paths as $path) {
                $this->addPathToZip($zip, $disk, $path);
            }

            $zip->close();

            $this->storageGateway->writeStream($disk, $destinationZip, FileStream::fromPath($tempZip));
        } finally {
            if (file_exists($tempZip)) {
                unlink($tempZip);
            }
        }

        $this->eventDispatcher->dispatch(
            ZipCreated::class,
            ['path' => $destinationZip->value]
        );

        return $destinationZip;
    }

    /**
     * @throws FileOperationException
     */
    public function unzip(Disk $disk, Path $archivePath, Path $destinationDir): void
    {
        $this->assertCanUnzip($disk, $archivePath);

        $tempZip = $this->copyArchiveToTemp($disk, $archivePath);

        $zipOpened = false;
        $zip       = new \ZipArchive();

        try {
            if ($zip->open($tempZip) !== true) {
                FileOperationException::throwWith(FileOperationError::INVALID_ARCHIVE);
            }
            $zipOpened = true;

            $this->validateAllZipEntries($zip);
            $this->extractAllZipEntries($zip, $disk, $destinationDir);
        } finally {
            if ($zipOpened) {
                $zip->close();
            }
            if (file_exists($tempZip)) {
                unlink($tempZip);
            }
        }

        $this->eventDispatcher->dispatch(
            ArchiveExtracted::class,
            [
                'path'        => $archivePath->value,
                'destination' => $destinationDir->value,
            ]
        );
    }

    /**
     * @throws FileOperationException
     */
    private function copyArchiveToTemp(Disk $disk, Path $archivePath): string
    {
        $tempZip = tempnam(sys_get_temp_dir(), 'sm_unzip_');
        if ($tempZip === false) {
            FileOperationException::throwWith(FileOperationError::ARCHIVE_OPERATION_FAILED);
        }

        $this->storageGateway->readStream($disk, $archivePath)->copyToPath($tempZip);

        return $tempZip;
    }

    /**
     * @throws FileOperationException
     */
    private function validateAllZipEntries(\ZipArchive $zip): void
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if ($entryName !== false) {
                $this->validateZipEntry($zip, $entryName);
            }
        }
    }

    private function extractAllZipEntries(\ZipArchive $zip, Disk $disk, Path $destinationDir): void
    {
        $normDestDir = $this->pathNormalizer->normalizePath($destinationDir->value);
        if ($normDestDir !== '/' && ! $this->storageGateway->exists($disk, new Path($normDestDir))) {
            $this->storageGateway->makeDirectory($disk, $normDestDir);
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if ($entryName !== false) {
                $this->extractZipEntry($zip, $disk, $entryName, $normDestDir);
            }
        }
    }

    /**
     * @param  Path[]  $paths
     *
     * @throws FileOperationException
     */
    private function assertCanZip(Disk $disk, array $paths, Path $destinationZip): void
    {
        $disk->assertWritable();

        if (! $this->supportsZip()) {
            FileOperationException::throwWith(FileOperationError::ZIP_EXTENSION_NOT_AVAILABLE);
        }

        if ($this->storageGateway->exists($disk, $destinationZip)) {
            FileOperationException::throwWith(FileOperationError::FILE_ALREADY_EXISTS);
        }

        foreach ($paths as $path) {
            if (! $this->storageGateway->exists($disk, $path)) {
                FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
            }
        }
    }

    private function addPathToZip(\ZipArchive $zip, Disk $disk, Path $path): void
    {
        $normPath = $this->pathNormalizer->normalizePath($path->value);

        if ($this->storageGateway->isFile($disk, $path)) {
            $content = $this->storageGateway->readStream($disk, $path)->getContents();
            $zip->addFromString(basename($normPath), $content);

            return;
        }

        $baseDirName = basename($normPath);
        $zip->addEmptyDir($baseDirName);

        $allFiles = $this->storageGateway->allFiles($disk, $normPath);
        foreach ($allFiles as $file) {
            $normFile = $this->pathNormalizer->normalizePath($file);
            $relFile  = substr($normFile, strlen($normPath) + 1);
            $content  = $this->storageGateway->readStream($disk, new Path($normFile))->getContents();
            $zip->addFromString($baseDirName . '/' . $relFile, $content);
        }

        $allDirs = $this->storageGateway->allDirectories($disk, $normPath);
        foreach ($allDirs as $dir) {
            $normDir = $this->pathNormalizer->normalizePath($dir);
            $relDir  = substr($normDir, strlen($normPath) + 1);
            $zip->addEmptyDir($baseDirName . '/' . $relDir);
        }
    }

    /**
     * @throws FileOperationException
     */
    private function assertCanUnzip(Disk $disk, Path $archivePath): void
    {
        $disk->assertWritable();

        if (! $this->supportsZip()) {
            FileOperationException::throwWith(FileOperationError::ZIP_EXTENSION_NOT_AVAILABLE);
        }

        if (! $this->storageGateway->exists($disk, $archivePath)) {
            FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
        }

        if (! str_ends_with(strtolower($archivePath->value), '.zip')) {
            FileOperationException::throwWith(FileOperationError::INVALID_ARCHIVE);
        }
    }

    /**
     * @throws FileOperationException
     */
    private function validateZipEntry(\ZipArchive $zip, string $entryName): void
    {
        $cleanEntry = ltrim(str_replace('\\', '/', $entryName), '/');
        $parts      = explode('/', $cleanEntry);
        if (in_array('..', $parts, true)) {
            FileOperationException::throwWith(FileOperationError::INVALID_PATH);
        }

        if ($cleanEntry === '' || str_ends_with($entryName, '/')) {
            return;
        }

        $extension = strtolower(pathinfo($cleanEntry, PATHINFO_EXTENSION));
        if (! $this->config->upload->isExtensionAllowed($extension)) {
            FileOperationException::throwWith(FileOperationError::FORBIDDEN_FILE_EXTENSION);
        }

        $entryStream = $zip->getStream($entryName);
        if ($entryStream === false) {
            FileOperationException::throwWith(FileOperationError::INVALID_ARCHIVE);
        }

        $sample = (new FileStream($entryStream))->getContents(self::MIME_SAMPLE_BYTES);

        if ($sample !== '') {
            $detectedMime = $this->mimeTypeChecker->detectBufferMimeType($sample);
            if (! $this->mimeTypeChecker->isMimeAllowedForExtension($extension, $detectedMime)) {
                FileOperationException::throwWith(FileOperationError::FORBIDDEN_FILE_MIME);
            }
        }
    }

    private function extractZipEntry(
        \ZipArchive $zip,
        Disk $disk,
        string $entryName,
        string $normDestDir,
    ): void {
        $cleanEntry = ltrim(str_replace('\\', '/', $entryName), '/');
        if ($cleanEntry === '') {
            return;
        }

        $targetPath = $normDestDir === '/' ? '/' . $cleanEntry : $normDestDir . '/' . $cleanEntry;
        $targetPath = $this->pathNormalizer->normalizePath($targetPath);

        if (str_ends_with($entryName, '/')) {
            if (! $this->storageGateway->exists($disk, new Path($targetPath))) {
                $this->storageGateway->makeDirectory($disk, $targetPath);
            }

            return;
        }

        $parentDir = dirname($targetPath);
        if ($parentDir !== '/' && ! $this->storageGateway->exists($disk, new Path($parentDir))) {
            $this->storageGateway->makeDirectory($disk, $parentDir);
        }

        $entryStream = $zip->getStream($entryName);
        if ($entryStream !== false) {
            $this->storageGateway->writeStream($disk, new Path($targetPath), new FileStream($entryStream));
        }
    }
}
