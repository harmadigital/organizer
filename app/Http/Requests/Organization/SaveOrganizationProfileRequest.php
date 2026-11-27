<?php

namespace App\Http\Requests\Organization;

use App\Http\Requests\JsonFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class SaveOrganizationProfileRequest extends JsonFormRequest
{
    /**
     * Access is decided by the route's "can:" middleware, as on every other screen.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'timezone' => ['required', 'timezone'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama organisasi',
            'short_name' => 'nama singkat',
            'timezone' => 'zona waktu',
            'address' => 'alamat',
            'city' => 'kota',
            'phone' => 'telepon',
            'email' => 'email',
            'website' => 'situs web',
        ];
    }
}
