<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\Validator;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Http\Request\ApiRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\Upload\Traits\ValidatesUploadId;
use Psr\Log\LoggerInterface;

class UploadChunkRequest extends ApiRequest
{
    use ValidatesUploadId;

    protected StorageManagerConfig $config;

    public function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->config = resolve(StorageManagerConfig::class);
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        $uploadChunkMaxSize = $this->config->upload->chunkMaxSize;

        return array_merge($this->getUploadIdRules(), [
            'chunk_index'    => ['required', 'integer', 'min:0'],
            'chunk_checksum' => ['required', 'string', 'regex:/^[a-f0-9]{32}$/i'],
            'file'           => ['required', 'file', 'min:1', 'max:' . ceil($uploadChunkMaxSize / 1024)],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $file = $this->file('file');
            if (! $file || ! $file->isValid()) {
                $validator->errors()->add(
                    'file',
                    (string) Lang::get('storage-manager::storage-manager.validation.invalid_file_upload')
                );

                return;
            }

            $chunkChecksum  = $this->string('chunk_checksum')->value();
            $actualChecksum = md5_file($file->getRealPath());
            if ($actualChecksum !== $chunkChecksum) {
                resolve(LoggerInterface::class)->warning('Upload chunk checksum mismatch', [
                    'uploadId'       => $this->string('upload_id')->value(),
                    'chunkIndex'     => $this->integer('chunk_index'),
                    'expected'       => $chunkChecksum,
                    'actual'         => $actualChecksum,
                ]);
                $validator->errors()->add(
                    'chunk_checksum',
                    (string) Lang::get('storage-manager::storage-manager.validation.chunk_checksum_mismatch')
                );
            }
        });
    }

    public function getUploadedFile(): UploadedFile
    {
        $file = $this->file('file');
        assert($file instanceof UploadedFile);

        return $file;
    }
}
