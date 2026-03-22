<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

/**
 * Sanitizes file and directory names by removing illegal characters, trimming whitespace, and ensuring valid formatting.
 * This helps prevent issues with file systems and ensures consistent naming conventions.
 */
class PathSanitizer
{
    /** Chars forbidden by most file systems */
    private const ILLEGAL_CHARS = '/[<>:"\/\\|?*\x00-\x1F\x7F\xA0\xAD#\[\]@!$&\'()+,;={}^~`]/u';

    private const WHITESPACE_REGEX = '/\s+/';

    public function sanitizeDirectoryName(string $directoryName, bool $beautify = true): string
    {
        // Replace illegal characters with a single space instead of removing or using hyphens
        $sanitized = preg_replace(self::ILLEGAL_CHARS, ' ', $directoryName);
        // Normalize whitespace but preserve spaces
        $sanitized = trim(preg_replace(self::WHITESPACE_REGEX, ' ', $sanitized));

        return $beautify ? $this->beautify($sanitized) : $sanitized;
    }

    public function sanitizeFileName(string $filename, bool $beautify = true): string
    {
        // Replace illegal characters with a space to avoid aggressive hyphenation
        $filename = preg_replace(self::ILLEGAL_CHARS, ' ', $filename);

        // Normalize whitespace and remove leading dots (hidden files) but preserve case
        $filename = trim(preg_replace(self::WHITESPACE_REGEX, ' ', $filename));
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
        $filename = preg_replace(self::WHITESPACE_REGEX, ' ', $filename);

        // Convert sequences like '._.' or '-.-' around dots to a single dot where appropriate
        $filename = preg_replace(['/[-_]*\.[-_]*/', '/\.{2,}/'], '.', $filename);

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
