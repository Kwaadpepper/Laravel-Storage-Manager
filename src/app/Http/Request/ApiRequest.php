<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

abstract class ApiRequest extends FormRequest
{
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
