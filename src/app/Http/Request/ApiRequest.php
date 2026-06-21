<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Kwaadpepper\LaravelStorageManager\Service\AuthService;

abstract class ApiRequest extends FormRequest
{
    protected AuthService $authService;

    public function prepareForValidation()
    {
        $this->authService  = resolve(AuthService::class);
    }

    public function authorize(): bool
    {
        return $this->authService->check();
    }

    /**
     * Handle a failed validation attempt.
     *
     * @return void
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        $exception = ($validator instanceof \Illuminate\Validation\Validator)
            ? $validator->getException()
            : new ValidationException($validator);

        throw (new $exception($validator))
            ->errorBag($this->errorBag);
    }
}
