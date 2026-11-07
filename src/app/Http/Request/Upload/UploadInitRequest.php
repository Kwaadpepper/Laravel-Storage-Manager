<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\Upload;

use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Http\Request\ApiRequest;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathSanitizer;
use Kwaadpepper\LaravelStorageManager\Rule\IsAllowedFileExtension;

class UploadInitRequest extends ApiRequest
{
    private StorageManagerConfig $config;

    private PathSanitizer $pathSanitizer;

    public function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->config        = resolve(StorageManagerConfig::class);
        $this->pathSanitizer = resolve(PathSanitizer::class);

        $fileName = $this->input('fileName');
        if (! is_string($fileName) || $fileName === '') {
            return;
        }

        if ($this->config->upload->sanitizeFilenames) {
            $this->merge([
                'fileName' => $this->pathSanitizer->sanitizeFileName($fileName),
            ]);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'fileName' => [
                'required',
                'string',
                'min:1',
                'max:255',
                new IsAllowedFileExtension($this->config->upload),
            ],
            'totalChunks' => ['required', 'integer', 'min:1'],
            'totalSize'   => ['required', 'numeric', 'min:1'],
        ];
    }
}
