<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Kwaadpepper\LaravelStorageManager\Exception\PathSanitizationException;

/**
 * Sanitizes file and directory names by removing illegal characters, trimming whitespace, and ensuring valid formatting.
 * This helps prevent issues with file systems and ensures consistent naming conventions.
 */
class PathSanitizer
{
    /** Chars forbidden by most file systems */
    public const string ILLEGAL_CHARS = '/[<>:"\/\\\\|?*\x00-\x1F\x7F]/u';

    private const string WHITESPACE_REGEX = '/\s+/';

    public function isValidName(mixed $name): bool
    {
        if (! is_string($name)) {
            return false;
        }

        $trimmed = trim($name);
        if ($trimmed === '') {
            return false;
        }

        if (in_array($trimmed, ['.', '..'], true)) {
            return false;
        }

        if (strlen($name) > 255) {
            return false;
        }

        return ! preg_match(self::ILLEGAL_CHARS, $name);
    }

    public function isValidFileName(mixed $name): bool
    {
        return $this->isValidName($name);
    }

    public function isValidDirectoryName(mixed $name): bool
    {
        return $this->isValidName($name);
    }

    public function sanitizeDirectoryName(string $directoryName, bool $beautify = true): string
    {
        // Replace illegal characters with a single space instead of removing or using hyphens
        $sanitized = preg_replace(self::ILLEGAL_CHARS, ' ', $directoryName)
          ?? throw new PathSanitizationException('Failed to sanitize directory name.');
        $sanitized = preg_replace(self::WHITESPACE_REGEX, ' ', $sanitized)
          ?? throw new PathSanitizationException('Failed to normalize whitespace in directory name.');
        // Normalize whitespace but preserve spaces
        $sanitized = trim($sanitized);

        return $beautify ? $this->beautify($sanitized) : $sanitized;
    }

    public function sanitizeFileName(string $filename, bool $beautify = true): string
    {
        // Replace illegal characters with a space to avoid aggressive hyphenation
        $filename = preg_replace(self::ILLEGAL_CHARS, ' ', $filename)
          ?? throw new PathSanitizationException('Failed to sanitize file name.');

        // Normalize whitespace and remove leading dots (hidden files) but preserve case
        $filename = trim(preg_replace(self::WHITESPACE_REGEX, ' ', $filename)
          ?? throw new PathSanitizationException('Failed to normalize whitespace in file name.'));
        $filename = ltrim($filename, '.');

        if ($beautify) {
            $filename = $this->beautify($filename);
        }

        // Handle the 255-byte limit (not characters)
        return $this->truncateToBytes($filename, 255);
    }

    private function beautify(string $filename): string
    {
        // Reduce multiple spaces to a single space but preserve case
        $filename = preg_replace(self::WHITESPACE_REGEX, ' ', $filename)
          ?? throw new PathSanitizationException('Failed to normalize whitespace in file name.');

        // Convert sequences like '._.' or '-.-' around dots to a single dot where appropriate
        $filename = preg_replace(['/[-_]*\.[-_]*/', '/\.{2,}/'], '.', $filename)
          ?? throw new PathSanitizationException('Failed to beautify file name.');

        // Trim surrounding spaces and any trailing dots
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
