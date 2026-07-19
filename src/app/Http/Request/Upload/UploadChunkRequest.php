<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\Upload;

use Illuminate\Validation\Validator;
use Kwaadpepper\LaravelStorageManager\Http\Request\ApiRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\Upload\Traits\ValidatesUploadId;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;

class UploadChunkRequest extends ApiRequest
{
    use ValidatesUploadId;

    protected ConfigRepository $configRepository;

    public function prepareForValidation()
    {
        parent::prepareForValidation();
        $this->configRepository = resolve(ConfigRepository::class);
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        $uploadChunkMaxSize = $this->configRepository->getUploadChunkMaxSize();

        return array_merge($this->getUploadIdRules(), [
            'chunk_index'    => ['required', 'integer', 'min:0'],
            'chunk_checksum' => ['required', 'string', 'regex:/^[a-f0-9]{32}$/i'],
            'file'           => ['required', 'file', 'min:1', 'max:' . ceil($uploadChunkMaxSize / 1024)],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $file = $this->file('file');
            if (! $file || ! $file->isValid()) {
                $validator->errors()->add('file', 'Invalid file upload.');

                return;
            }

            $chunkChecksum  = $this->string('chunk_checksum')->value();
            $actualChecksum = md5_file($file->getRealPath());
            if ($actualChecksum !== $chunkChecksum) {
                $validator->errors()->add('chunk_checksum', 'Chunk checksum mismatch.');
            }
        });
    }
}
