<?php

namespace App\Http\Requests\Events;

use App\Enums\EventPhase;
use App\Http\Requests\JsonFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ChangeEventPhaseRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('changePhase', $this->route('event'));
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'phase' => ['required', Rule::enum(EventPhase::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['phase' => 'fase', 'note' => 'catatan'];
    }
}
