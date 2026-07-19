<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request;

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

class SearchRequest extends RequestWithDisk
{
    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'paths'     => 'required|array',
            'paths.*'   => 'required|string',
            'query'     => 'nullable|string',
            'extension' => 'nullable|string',
            'minSize'   => 'nullable|integer|min:0',
            'maxSize'   => 'nullable|integer|min:0|gte:minSize',
        ]);
    }

    /**
     * @return Path[]
     */
    public function getPaths(): array
    {
        /** @var string[] $pathsInput */
        $pathsInput = $this->input('paths', []);

        return array_map(fn (string $path) => new Path($path), $pathsInput);
    }

    public function getSearchQuery(): ?string
    {
        return $this->string('query')->value() ?: null;
    }

    public function getExtension(): ?string
    {
        return $this->string('extension')->value() ?: null;
    }

    public function getMinSize(): ?int
    {
        $minSize = $this->input('minSize');

        return is_numeric($minSize) ? (int) $minSize : null;
    }

    public function getMaxSize(): ?int
    {
        $maxSize = $this->input('maxSize');

        return is_numeric($maxSize) ? (int) $maxSize : null;
    }
}
