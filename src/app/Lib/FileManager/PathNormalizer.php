<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

class PathNormalizer
{
    /**
     * Normalizes a given path by:
     * - Replacing backslashes with forward slashes.
     * - Removing redundant slashes.
     * - Resolving '.' and '..' segments.
     * Ensuring the path starts with a single slash and does not end with a slash (unless it's the root).
     */
    public function normalizePath(string $path): string
    {
        $segments = preg_split('#[\\\\/]+#', trim($path), -1, PREG_SPLIT_NO_EMPTY);
        $stack    = [];

        foreach ($segments as $segment) {
            if ($segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($stack);
            } else {
                $stack[] = $segment;
            }
        }

        return '/' . implode('/', $stack);
    }
}
