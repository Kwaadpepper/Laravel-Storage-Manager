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

    public function sanitizeDirectoryName(string $directoryName, bool $beautify = true): string
    {
        $sanitized = preg_replace(self::ILLEGAL_CHARS, '', $directoryName);
        $sanitized = trim(preg_replace('/\s+/', ' ', $sanitized));

        return $beautify ? $this->beautify($sanitized) : $sanitized;
    }

    public function sanitizeFileName(string $filename, bool $beautify = true): string
    {
        // Replace illegal characters with a hyphen
        $filename = preg_replace(self::ILLEGAL_CHARS, '-', $filename);

        // Remove leading dots/hyphens (hidden or invalid files)
        $filename = ltrim($filename, '.-');

        if ($beautify) {
            $filename = $this->beautify($filename);
        }

        // Handle the 255-byte limit (not characters)
        return $this->truncateToBytes($filename, 255);
    }

    private function beautify(string $filename): string
    {
        // Reduce multiple spaces, underscores, or hyphens to a single hyphen
        $filename = preg_replace('/[ _-]+/', '-', $filename);

        // Clean up sequences of dots (e.g., '...') to a single dot, but preserve valid extensions
        $filename = preg_replace(['/-*\.-*/', '/\.{2,}/'], '.', $filename);

        // Trim any remaining unwanted characters
        return trim($filename, '.-');
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
