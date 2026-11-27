<?php

namespace App\Http\Requests\Events;

use App\Enums\EventStatus;
use App\Http\Requests\JsonFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ChangeEventStatusRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('changeStatus', $this->route('event'));
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(EventStatus::class)],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['status' => 'status', 'reason' => 'alasan'];
    }
}
