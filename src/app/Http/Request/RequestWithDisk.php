<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request;

use Illuminate\Validation\Rule;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Service\AuthService;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;

abstract class RequestWithDisk extends ApiRequest
{
    private AuthService $authService;

    private DiskService $diskService;

    public function prepareForValidation()
    {
        $this->authService = resolve(AuthService::class);
        $this->diskService = resolve(DiskService::class);
    }

    public function authorize(): bool
    {
        return $this->authService->check();
    }

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
            'disk' => trans('storage-manager::storage-manager.attribute.disk'),
        ];
    }

    public function getDisk(): Disk
    {
        return $this->diskService->getDisk(
            $this->string('disk')->value()
        );
    }
}
