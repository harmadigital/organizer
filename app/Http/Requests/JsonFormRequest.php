<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base for Form Requests behind AJAX screens.
 *
 * A failed validation answers with HTTP 422 in the application's JSON
 * envelope: {status: false, data: <first message>, errors: {field: [messages]}}.
 */
abstract class JsonFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        if (! $this->expectsJson()) {
            parent::failedValidation($validator);
        }

        throw new HttpResponseException(response()->json([
            'status' => false,
            'data' => $validator->errors()->first(),
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
