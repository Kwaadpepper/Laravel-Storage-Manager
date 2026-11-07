<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request;

use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\Rule;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;

abstract class RequestWithDisk extends ApiRequest
{
    protected DiskService $diskService;

    public function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->diskService = resolve(DiskService::class);
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'disk' => [
                'required',
                'string',
                Rule::in($this->diskService->getDiskNamesList()),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'disk' => (string) Lang::get('storage-manager::storage-manager.attribute.disk'),
        ];
    }

    public function getDisk(): Disk
    {
        return $this->diskService->getDisk(
            $this->string('disk')->value()
        );
    }
}
