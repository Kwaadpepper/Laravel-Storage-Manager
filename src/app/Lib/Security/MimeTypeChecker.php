<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Security;

use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Mime\MimeTypesInterface;

readonly class MimeTypeChecker
{
    public const array DANGEROUS_MIMES = [
        'text/x-php',
        'application/x-php',
        'application/x-httpd-php',
        'application/x-executable',
        'application/x-sharedlib',
        'application/x-shellscript',
        'text/x-shellscript',
        'text/x-python',
        'text/x-perl',
        'application/x-msdownload',
        'application/x-dosexec',
        'image/svg+xml',
        'image/svg',
        'text/xml',
        'application/xml',
        'application/x-xml',
    ];

    private const array DANGEROUS_EXTENSIONS = [
        'svg',
        'xml',
        'php',
        'phar',
        'phtml',
        'sh',
        'exe',
        'bat',
        'cmd',
    ];

    private const array PLAIN_TEXT_EXTENSIONS = [
        'csv',
        'tsv',
        'txt',
        'md',
    ];

    public const string DEFAULT_MIME = 'application/octet-stream';

    public const array MIME_OVERRIDES = [
        'mp4'  => 'video/mp4',
        'm4v'  => 'video/mp4',
        'mov'  => 'video/quicktime',
        'webm' => 'video/webm',
        'ogg'  => 'video/ogg',
        'ogv'  => 'video/ogg',
        'mkv'  => 'video/x-matroska',
        'mp3'  => 'audio/mpeg',
        'wav'  => 'audio/wav',
        'flac' => 'audio/flac',
        'aac'  => 'audio/aac',
        'm4a'  => 'audio/mp4',
        'opus' => 'audio/opus',
    ];

    private MimeTypesInterface $mimeTypes;

    public function __construct()
    {
        $this->mimeTypes = MimeTypes::getDefault();
    }

    public function guessMimeTypeFromExtension(string $extension, string $fallback = self::DEFAULT_MIME): string
    {
        $ext = strtolower(ltrim($extension, '.'));
        if ($ext === '') {
            return $fallback;
        }

        if (isset(self::MIME_OVERRIDES[$ext])) {
            return self::MIME_OVERRIDES[$ext];
        }

        return $this->mimeTypes->getMimeTypes($ext)[0] ?? $fallback;
    }

    public function detectMimeType(string $path): string
    {
        if (! file_exists($path) || ! is_readable($path)) {
            return self::DEFAULT_MIME;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($path);

        return is_string($mime) && $mime !== '' ? $mime : self::DEFAULT_MIME;
    }

    public function detectBufferMimeType(string $content): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->buffer($content);

        return is_string($mime) && $mime !== '' ? $mime : self::DEFAULT_MIME;
    }

    public function isDangerousMime(string $mimeType): bool
    {
        return in_array(strtolower(trim($mimeType)), self::DANGEROUS_MIMES, true);
    }

    public function isMimeAllowedForExtension(string $extension, string $detectedMime): bool
    {
        $detectedMime = strtolower(trim($detectedMime));
        $extension    = strtolower(ltrim($extension, '.'));

        if (in_array($extension, self::DANGEROUS_EXTENSIONS, true)) {
            return false;
        }

        if ($this->isDangerousMime($detectedMime)) {
            return false;
        }

        $expectedMimes = array_map(
            fn (string $m): string => strtolower($m),
            $this->mimeTypes->getMimeTypes($extension)
        );

        return match (true) {
            empty($expectedMimes)                                                                     => true,
            in_array($detectedMime, $expectedMimes, true)                                             => true,
            $detectedMime === 'text/plain' && in_array($extension, self::PLAIN_TEXT_EXTENSIONS, true) => true,
            default                                                                                   => false,
        };
    }
}
