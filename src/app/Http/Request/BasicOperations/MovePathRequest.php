<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithPath;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Rule\IsValidPath;

class MovePathRequest extends RequestWithPath
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
            'destination_dir' => ['required', 'string', new IsValidPath()],
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'destination_dir' => trans('storage-manager::storage-manager.attribute.destination_dir'),
        ]);
    }

    public function getDestinationDir(): Path
    {
        $path           = $this->string('destination_dir')->value();
        $normalizedPath = $this->pathNormalizer->normalizePath($path);

        return new Path($normalizedPath);
    }
}
