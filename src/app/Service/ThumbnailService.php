<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use GdImage;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

class ThumbnailService
{
    private const array SUPPORTED_EXTENSIONS = [
        'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'avif',
    ];

    public function __construct(
        private readonly StorageManagerConfig $config,
        private readonly StorageGateway $storageGateway,
    ) {
    }

    public function isSupportedExtension(?string $extension): bool
    {
        if ($extension === null || $extension === '') {
            return false;
        }

        return in_array(strtolower($extension), self::SUPPORTED_EXTENSIONS, true);
    }

    public function getOrCreateThumbnail(Disk $disk, Path $path, FilePathProperties $properties): ?string
    {
        $extension = $properties->extension;
        if (! $this->isSupportedExtension($extension)) {
            return null;
        }

        $cacheDir = storage_path('framework/cache/sm-thumbnails/' . $disk->name);
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $ext          = strtolower($extension);
        $fileSize     = $properties->size;
        $lastModified = $properties->timestamp->getTimestamp();
        $width        = $this->config->thumbnails->width;
        $height       = $this->config->thumbnails->height;
        $quality      = $this->config->thumbnails->quality;

        if ($ext === 'svg') {
            return $this->handleSvgThumbnail($disk, $path, $cacheDir, $fileSize, $lastModified);
        }

        $cacheKey       = sha1("{$disk->name}:{$path->value}:{$fileSize}:{$lastModified}:{$width}x{$height}:{$quality}") . '.webp';
        $cachedFilePath = $cacheDir . '/' . $cacheKey;

        if (file_exists($cachedFilePath) && filesize($cachedFilePath) > 0) {
            return $cachedFilePath;
        }

        return $this->generateRasterThumbnail($disk, $path, $cachedFilePath, $width, $height, $quality);
    }

    private function handleSvgThumbnail(Disk $disk, Path $path, string $cacheDir, int $fileSize, int $lastModified): ?string
    {
        $cacheKey       = sha1("{$disk->name}:{$path->value}:{$fileSize}:{$lastModified}") . '.svg';
        $cachedFilePath = $cacheDir . '/' . $cacheKey;

        if (file_exists($cachedFilePath) && filesize($cachedFilePath) > 0) {
            return $cachedFilePath;
        }

        $content = $this->storageGateway->readStream($disk, $path)->getContents();

        if ($content === '') {
            return null;
        }

        file_put_contents($cachedFilePath, $content);

        return $cachedFilePath;
    }

    private function generateRasterThumbnail(
        Disk $disk,
        Path $path,
        string $cachedFilePath,
        int $maxWidth,
        int $maxHeight,
        int $quality
    ): ?string {
        if (! extension_loaded('gd')) {
            return null;
        }

        $content = $this->storageGateway->readStream($disk, $path)->getContents();

        if ($content === '') {
            return null;
        }

        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        $srcImg = @imagecreatefromstring($content);
        if (! ($srcImg instanceof GdImage)) {
            return null;
        }

        $srcW = imagesx($srcImg);
        $srcH = imagesy($srcImg);

        $scale = min($maxWidth / $srcW, $maxHeight / $srcH);
        if ($scale < 1.0) {
            $dstW = max(1, (int) round($srcW * $scale));
            $dstH = max(1, (int) round($srcH * $scale));
        } else {
            $dstW = $srcW;
            $dstH = $srcH;
        }

        $dstImg = imagecreatetruecolor($dstW, $dstH);
        if (! ($dstImg instanceof GdImage)) {
            return null;
        }

        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
        $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
        if ($transparent !== false) {
            imagefilledrectangle($dstImg, 0, 0, $dstW, $dstH, $transparent);
        }

        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        imagewebp($dstImg, $cachedFilePath, $quality);

        return file_exists($cachedFilePath) ? $cachedFilePath : null;
    }
}
