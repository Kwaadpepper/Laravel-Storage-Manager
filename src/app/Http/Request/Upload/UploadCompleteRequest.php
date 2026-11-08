<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\Upload;

use Illuminate\Validation\Rule;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Enum\DuplicatePolicy;
use Kwaadpepper\LaravelStorageManager\Enum\FilenamePolicy;
use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithPath;
use Kwaadpepper\LaravelStorageManager\Http\Request\Upload\Traits\ValidatesUploadId;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathSanitizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Rule\IsAllowedFileExtension;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;

class UploadCompleteRequest extends RequestWithPath
{
    use ValidatesUploadId;

    private StorageManagerConfig $config;

    private PathSanitizer $pathSanitizer;

    private DiskService $uploadDiskService;

    private ?Disk $targetDisk = null;

    public function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->config            = resolve(StorageManagerConfig::class);
        $this->pathSanitizer     = resolve(PathSanitizer::class);
        $this->uploadDiskService = resolve(DiskService::class);

        $diskName = $this->input('disk');
        if (is_string($diskName) && $diskName !== '') {
            try {
                $this->targetDisk = $this->uploadDiskService->getDisk($diskName);
            } catch (\InvalidArgumentException) {
                $this->targetDisk = null;
            }
        } else {
            $this->targetDisk = $this->config->disks->getDefaultDisk();
        }

        $fileName = $this->input('fileName');
        if (! is_string($fileName) || $fileName === '') {
            return;
        }

        if ($this->config->upload->sanitizeFilenames) {
            $policy = $this->targetDisk->filenamePolicy ?? FilenamePolicy::STRICT;
            $this->merge([
                'fileName' => $this->pathSanitizer->sanitizeFileName($fileName, true, $policy),
            ]);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), $this->getUploadIdRules(), [
            'fileName'        => [
                'required',
                'string',
                'min:1',
                'max:255',
                new IsAllowedFileExtension($this->config->upload, $this->targetDisk),
            ],
            'totalChunks'     => ['required', 'integer', 'min:1'],
            'duplicatePolicy' => ['nullable', 'string', Rule::enum(DuplicatePolicy::class)],
        ]);
    }
}
