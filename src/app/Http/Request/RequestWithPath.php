<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request;

use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Rule\IsValidPath;

class RequestWithPath extends RequestWithDisk
{
    private PathNormalizer $pathNormalizer;

    public function prepareForValidation()
    {
        parent::prepareForValidation();
        $this->pathNormalizer = resolve(PathNormalizer::class);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'path' => ['required', 'string', new IsValidPath()],
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'path' => trans('storage-manager::storage-manager.attribute.path'),
        ]);
    }

    public function getPath(): Path
    {
        $path           = $this->string('path')->value();
        $normalizedPath = $this->pathNormalizer->normalizePath($path);

        return new Path($normalizedPath);
    }
}
