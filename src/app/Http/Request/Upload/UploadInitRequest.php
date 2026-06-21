<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\Upload;

use Kwaadpepper\LaravelStorageManager\Http\Request\ApiRequest;

class UploadInitRequest extends ApiRequest
{
    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'fileName'    => ['required', 'string', 'min:1', 'max:255'],
            'totalChunks' => ['required', 'integer', 'min:1'],
            'totalSize'   => ['required', 'numeric', 'min:1'],
        ];
    }
}
