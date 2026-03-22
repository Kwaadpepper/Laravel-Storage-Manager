<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use InvalidArgumentException;

class PathPropertyFactory
{
    public static function fromArray(array $metadata): PathProperties
    {
        $type = $metadata['type'] ?? throw new InvalidArgumentException("Le champ 'type' est obligatoire.");

        return match ($type) {
            'file'  => self::makeFile($metadata),
            'dir'   => self::makeDirectory($metadata),
            default => throw new InvalidArgumentException("Type de chemin invalide : {$type}"),
        };
    }

    private static function makeFile(array $data): FilePathProperties
    {
        return new FilePathProperties(
            path: new Path($data['path']),
            basename: $data['basename']   ?? basename($data['path']),
            dirname: $data['dirname']     ?? dirname($data['path']),
            extension: $data['extension'] ?? '',
            filename: $data['filename']   ?? '',
            size: $data['size']           ?? 0,
            timestamp: self::parseTimestamp($data['timestamp'] ?? 'now'),
            visibility: self::parseVisibility($data['visibility'] ?? Filesystem::VISIBILITY_PRIVATE)
        );
    }

    private static function makeDirectory(array $data): DirectoryPathProperties
    {
        return new DirectoryPathProperties(
            path: new Path($data['path']),
            basename: $data['basename'] ?? basename($data['path']),
            dirname: $data['dirname']   ?? dirname($data['path']),
            timestamp: self::parseTimestamp($data['timestamp'] ?? 'now'),
            visibility: self::parseVisibility($data['visibility'] ?? Filesystem::VISIBILITY_PRIVATE)
        );
    }

    private static function parseTimestamp(string | int $timestamp): DateTimeInterface
    {
        try {
            // Support du format string ou timestamp UNIX
            return is_numeric($timestamp)
                ? new DateTimeImmutable('@' . $timestamp)
                : new DateTimeImmutable($timestamp);
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Format de timestamp invalide : {$timestamp}", 0, $e);
        }
    }

    private static function parseVisibility(string $visibility): PathVisibility
    {
        return match ($visibility) {
            Filesystem::VISIBILITY_PUBLIC  => PathVisibility::PUBLIC,
            Filesystem::VISIBILITY_PRIVATE => PathVisibility::PRIVATE,
            default                        => throw new InvalidArgumentException("Visibilité invalide : {$visibility}"),
        };
    }
}
