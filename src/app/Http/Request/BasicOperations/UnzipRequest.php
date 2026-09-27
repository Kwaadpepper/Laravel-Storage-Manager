<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithPath;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Rule\IsValidPath;

class UnzipRequest extends RequestWithPath
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
            'destinationDir' => ['nullable', 'string', new IsValidPath()],
        ]);
    }

    public function getDestinationDir(): Path
    {
        $destinationDir = $this->string('destinationDir')->value();

        if (empty($destinationDir)) {
            $normalizedPath = $this->pathNormalizer->normalizePath($this->getPath()->value);

            return new Path(dirname($normalizedPath));
        }

        $normalized = $this->pathNormalizer->normalizePath($destinationDir);

        return new Path($normalized);
    }
}
