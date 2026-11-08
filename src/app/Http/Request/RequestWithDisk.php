<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request;

use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        $diskName = $this->string('disk')->value();

        try {
            return $this->diskService->getDisk($diskName);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages([
                'disk' => [(string) Lang::get('validation.in', ['attribute' => 'disk'])],
            ]);
        }
    }
}
