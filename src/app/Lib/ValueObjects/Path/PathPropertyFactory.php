<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use InvalidArgumentException;

class PathPropertyFactory
{
    /**
     * @param  array{
     * type:string,
     * path:\Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path|string,
     * basename?:string,
     * dirname?:string,
     * extension?:string,
     * filename?:string,
     * size?:int,
     * timestamp?:string|int|null,
     * visibility?:string|null}  $metadata
     */
    public static function fromArray(array $metadata): PathProperties
    {
        $type = $metadata['type'] ?? throw new InvalidArgumentException("Le champ 'type' est obligatoire.");

        return match ($type) {
            'file'  => self::makeFile($metadata),
            'dir'   => self::makeDirectory($metadata),
            default => throw new InvalidArgumentException("Type de chemin invalide : {$type}"),
        };
    }

    /**
     * @param  array{
     * path:\Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path|string,
     * basename?:string,
     * dirname?:string,
     * extension?:string,
     * filename?:string,
     * size?:int,
     * timestamp?:string|int|null,
     * visibility?:string|null}  $data
     */
    private static function makeFile(array $data): FilePathProperties
    {
        $path = $data['path'] instanceof Path ? $data['path'] : new Path($data['path']);

        return new FilePathProperties(
            path: $path,
            basename: $data['basename']   ?? basename($path->value),
            dirname: $data['dirname']     ?? dirname($path->value),
            extension: $data['extension'] ?? '',
            filename: $data['filename']   ?? '',
            size: $data['size']           ?? 0,
            timestamp: self::parseTimestamp($data['timestamp'] ?? 'now'),
            visibility: self::parseVisibility($data['visibility'] ?? Filesystem::VISIBILITY_PRIVATE)
        );
    }

    /**
     * @param  array{
     * path:\Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path|string,
     * basename?:string,
     * dirname?:string,
     * extension?:string,
     * filename?:string,
     * size?:int,
     * timestamp?:string|int|null,
     * visibility?:string|null}  $data
     */
    private static function makeDirectory(array $data): DirectoryPathProperties
    {
        $path = $data['path'] instanceof Path ? $data['path'] : new Path($data['path']);

        return new DirectoryPathProperties(
            path: $path,
            basename: $data['basename'] ?? basename($path->value),
            dirname: $data['dirname']   ?? dirname($path->value),
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
