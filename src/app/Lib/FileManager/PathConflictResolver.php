<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Psr\Log\LoggerInterface;

readonly class PathConflictResolver
{
    public function __construct(
        private PathNormalizer $pathNormalizer,
        private StorageGateway $storageGateway,
        private LoggerInterface $logger,
    ) {
    }

    public function resolveAvailablePath(Disk $disk, Path $desiredDestination, string $format = '%s (%d)%s'): Path
    {
        $normalized = $this->pathNormalizer->normalizePath($desiredDestination->value);
        $candidate  = new Path($normalized);

        if (! $this->storageGateway->exists($disk, $candidate)) {
            $this->logger->debug('Path is available without conflict', [
                'disk' => $disk->name,
                'path' => $candidate->value,
            ]);

            return $candidate;
        }

        $pathInfo = pathinfo($normalized);
        $dirname  = $pathInfo['dirname'] ?? '';
        $parent   = $dirname === '.' || $dirname === '/' ? '' : $dirname;
        $filename = $pathInfo['filename'];
        $ext      = isset($pathInfo['extension']) && $pathInfo['extension'] !== '' ? '.' . $pathInfo['extension'] : '';

        $counter = 1;
        while ($this->storageGateway->exists($disk, $candidate)) {
            if (str_contains($format, '%d')) {
                $newName = sprintf($format, $filename, $counter, $ext);
            } else {
                $suffix  = $counter > 1 ? ' ' . $counter : '';
                $newName = sprintf($format, $filename, $suffix, $ext);
            }
            $candidateStr = $this->pathNormalizer->normalizePath($parent . '/' . $newName);
            $candidate    = new Path($candidateStr);
            $counter++;
        }

        $this->logger->info('Resolved conflicting path to available alternative', [
            'disk'     => $disk->name,
            'desired'  => $desiredDestination->value,
            'resolved' => $candidate->value,
            'attempts' => $counter - 1,
        ]);

        return $candidate;
    }
}
