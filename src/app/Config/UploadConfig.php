<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Config;

use Kwaadpepper\LaravelStorageManager\Enum\DuplicatePolicy;

final readonly class UploadConfig
{
    public const array UNSUPPORTED_EXTENSIONS = [
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

    /**
     * @param  list<string>  $allowedExtensions
     */
    public function __construct(
        public int $chunkMinSize,
        public int $chunkMaxSize,
        public ?string $tempDisk,
        public string $tempPath,
        public DuplicatePolicy $duplicatePolicy,
        public bool $sanitizeFilenames,
        public array $allowedExtensions,
        public ?int $maxFileSize = null,
    ) {
    }

    public function isExtensionAllowed(string $extension): bool
    {
        $normalized = strtolower(ltrim(mb_trim($extension), '.'));

        if ($normalized === '' || in_array($normalized, self::UNSUPPORTED_EXTENSIONS, true)) {
            return false;
        }

        return in_array($normalized, $this->allowedExtensions, true);
    }
}
