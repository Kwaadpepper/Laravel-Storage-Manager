<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Kwaadpepper\LaravelStorageManager\Enum\FilenamePolicy;
use Kwaadpepper\LaravelStorageManager\Exception\PathSanitizationException;

/**
 * Sanitizes file and directory names by removing illegal characters, trimming whitespace, and ensuring valid formatting.
 * This helps prevent issues with file systems and ensures consistent naming conventions.
 */
class PathSanitizer
{
    private const string WHITESPACE_REGEX = '/\s+/';

    public function isValidName(mixed $name, FilenamePolicy $policy = FilenamePolicy::STRICT): bool
    {
        if (! is_string($name)) {
            return false;
        }

        $trimmed = trim($name);
        if (
            $trimmed === '' || in_array($trimmed, ['.', '..'], true) || strlen($name) > 255
        ) {
            return false;
        }

        return ! preg_match($policy->illegalCharsRegex(), $name);
    }

    public function isValidFileName(mixed $name, FilenamePolicy $policy = FilenamePolicy::STRICT): bool
    {
        return $this->isValidName($name, $policy);
    }

    public function isValidDirectoryName(mixed $name, FilenamePolicy $policy = FilenamePolicy::STRICT): bool
    {
        return $this->isValidName($name, $policy);
    }

    public function sanitizeDirectoryName(
        string $directoryName,
        bool $beautify = true,
        FilenamePolicy $policy = FilenamePolicy::STRICT
    ): string {
        if ($policy === FilenamePolicy::STRICT) {
            $directoryName = str_replace(':', '-', $directoryName);
        }

        $sanitized = preg_replace($policy->illegalCharsRegex(), ' ', $directoryName)
          ?? throw new PathSanitizationException('Failed to sanitize directory name.');
        $sanitized = preg_replace(self::WHITESPACE_REGEX, ' ', $sanitized)
          ?? throw new PathSanitizationException('Failed to normalize whitespace in directory name.');
        $sanitized = trim($sanitized);

        return $beautify ? $this->beautify($sanitized) : $sanitized;
    }

    public function sanitizeFileName(
        string $filename,
        bool $beautify = true,
        FilenamePolicy $policy = FilenamePolicy::STRICT
    ): string {
        if ($policy === FilenamePolicy::STRICT) {
            $filename = str_replace(':', '-', $filename);
        }

        $filename = preg_replace($policy->illegalCharsRegex(), ' ', $filename)
          ?? throw new PathSanitizationException('Failed to sanitize file name.');

        $filename = trim(preg_replace(self::WHITESPACE_REGEX, ' ', $filename)
          ?? throw new PathSanitizationException('Failed to normalize whitespace in file name.'));
        $filename = ltrim($filename, '.');

        if ($beautify) {
            $filename = $this->beautify($filename);
        }

        return $this->truncateToBytes($filename, 255);
    }

    private function beautify(string $filename): string
    {
        $filename = preg_replace(self::WHITESPACE_REGEX, ' ', $filename)
          ?? throw new PathSanitizationException('Failed to normalize whitespace in file name.');

        $filename = preg_replace(['/[-_]*\.[-_]*/', '/\.{2,}/'], '.', $filename)
          ?? throw new PathSanitizationException('Failed to beautify file name.');

        return trim($filename, ' .-_');
    }

    private function truncateToBytes(string $filename, int $limit): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name      = pathinfo($filename, PATHINFO_FILENAME);

        $extPart       = $extension !== '' ? ".$extension" : '';
        $maxNameLength = $limit - strlen($extPart);

        $truncatedName = mb_strcut($name, 0, max(0, $maxNameLength), 'UTF-8');

        return $truncatedName . $extPart;
    }
}
