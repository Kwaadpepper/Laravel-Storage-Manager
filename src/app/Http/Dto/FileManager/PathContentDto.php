<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PaginatedDirectoryContent;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeFile;

final readonly class PathContentDto implements Dto
{
    public function __construct(
        public PaginatedDirectoryContent $content
    ) {
    }

    /**
     * @return array{
     *     directories: list<array{path: string, hasSubDirectories: bool, visibility: string|null}>,
     *     files: list<array{path: string, size: int, extension: string|null, visibility: string|null, publicUrl: string|null}>,
     *     pagination: array{currentPage: int, perPage: int, totalFiles: int, totalDirectories: int, totalPages: int, hasMore: bool}
     * }
     */
    public function jsonSerialize(): mixed
    {
        return [
            'directories' => array_map(fn (PathTreeDirectory $dir): array => [
                'path'              => (string) $dir->path,
                'hasSubDirectories' => $dir->hasSubDirectories,
                'visibility'        => $dir->visibility?->value,
            ], $this->content->directories),
            'files' => array_map(fn (PathTreeFile $file): array => [
                'path'       => (string) $file->path,
                'size'       => $file->size,
                'extension'  => $file->extension,
                'visibility' => $file->visibility?->value,
                'publicUrl'  => $file->publicUrl,
            ], $this->content->files),
            'pagination' => [
                'currentPage'      => $this->content->pagination->currentPage,
                'perPage'          => $this->content->pagination->perPage,
                'totalFiles'       => $this->content->pagination->totalFiles,
                'totalDirectories' => $this->content->pagination->totalDirectories,
                'totalPages'       => $this->content->pagination->totalPages,
                'hasMore'          => $this->content->pagination->hasMore,
            ],
        ];
    }
}
