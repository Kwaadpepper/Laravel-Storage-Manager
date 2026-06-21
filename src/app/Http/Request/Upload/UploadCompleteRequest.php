<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\Upload;

use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithPath;
use Kwaadpepper\LaravelStorageManager\Http\Request\Upload\Traits\ValidatesUploadId;

class UploadCompleteRequest extends RequestWithPath
{
    use ValidatesUploadId;

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), $this->getUploadIdRules(), [
            'fileName'    => ['required', 'string'],
            'totalChunks' => ['required', 'integer', 'min:1'],
        ]);
    }
}
