<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithDisk;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathSanitizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Rule\IsValidPath;

class ZipRequest extends RequestWithDisk
{
    private PathSanitizer $pathSanitizer;

    public function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->pathSanitizer = resolve(PathSanitizer::class);

        $destination = $this->string('destination')->value();

        if (empty($destination)) {
            return;
        }

        $dirName  = dirname($destination);
        $baseName = basename($destination);

        if (! str_ends_with(strtolower($baseName), '.zip')) {
            $baseName .= '.zip';
        }

        $sanitizedBaseName = $this->pathSanitizer->sanitizeFileName($baseName);
        $finalDestination  = $dirName === '.' || $dirName === '/'
            ? '/' . ltrim($sanitizedBaseName, '/')
            : rtrim($dirName, '/') . '/' . $sanitizedBaseName;

        $this->merge([
            'destination' => $finalDestination,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'paths'       => ['required', 'array', 'min:1'],
            'paths.*'     => ['required', 'string', 'min:1', 'starts_with:/', new IsValidPath()],
            'destination' => ['required', 'string', 'min:1', 'starts_with:/', 'ends_with:.zip', new IsValidPath()],
        ]);
    }

    /**
     * @return Path[]
     */
    public function getPaths(): array
    {
        /** @var string[] $paths */
        $paths = $this->input('paths', []);

        return array_map(
            fn (string $path): Path => new Path($path),
            $paths
        );
    }

    public function getDestination(): Path
    {
        return new Path($this->string('destination')->value());
    }
}
