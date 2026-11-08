<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request;

use Illuminate\Support\Facades\Lang;
use Kwaadpepper\LaravelStorageManager\Enum\FileSortField;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\PageNumber;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\PageSize;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\Pagination;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Rule\IsValidPath;

class RequestWithPath extends RequestWithDisk
{
    private PathNormalizer $pathNormalizer;

    public function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->pathNormalizer = resolve(PathNormalizer::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'path'       => ['required', 'string', new IsValidPath()],
            'page'       => ['nullable', 'integer', 'min:1'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:200'],
            'sort_by'    => ['nullable', 'string', 'in:name,size,extension,visibility,last_modified'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'path' => (string) Lang::get('storage-manager::storage-manager.attribute.path'),
        ]);
    }

    public function getPath(): Path
    {
        $path           = $this->string('path')->value();
        $normalizedPath = $this->pathNormalizer->normalizePath($path);

        return new Path($normalizedPath);
    }

    public function getPagination(): Pagination
    {
        $page    = max(1, $this->integer('page', 1));
        $perPage = max(1, min(PageSize::MAX_PAGE_SIZE, $this->integer('per_page', PageSize::DEFAULT_SIZE)));

        return Pagination::ofPage(
            new PageNumber($page),
            new PageSize($perPage)
        );
    }

    public function getSortBy(): ?FileSortField
    {
        $sortBy = $this->string('sort_by')->value();

        return $sortBy !== '' ? FileSortField::tryFrom($sortBy) : null;
    }

    public function getSortDirection(): \SortDirection
    {
        $order = strtolower($this->string('sort_order', 'asc')->value());

        return $order === 'desc' ? \SortDirection::Descending : \SortDirection::Ascending;
    }
}
