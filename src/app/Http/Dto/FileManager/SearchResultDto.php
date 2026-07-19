<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\SearchResult;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeFile;

final readonly class SearchResultDto implements Dto
{
    public function __construct(
        public SearchResult $searchResult,
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'directoriesToScan'  => array_map(fn ($path) => (string) $path, $this->searchResult->directoriesToScan),
            'matchedDirectories' => array_map(fn (PathTreeDirectory $dir) => [
                'path'              => $dir->path->value,
                'hasSubDirectories' => $dir->hasSubDirectories,
                'visibility'        => $dir->visibility?->value,
            ], $this->searchResult->matchedDirectories),
            'matchedFiles'       => array_map(fn (PathTreeFile $file) => [
                'path'       => $file->path->value,
                'size'       => $file->size,
                'extension'  => empty($file->extension) ? null : $file->extension,
                'visibility' => $file->visibility?->value,
                'publicUrl'  => $file->publicUrl,
            ], $this->searchResult->matchedFiles),
        ];
    }
}
