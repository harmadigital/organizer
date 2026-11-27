<?php

namespace App\Http\Requests\Events;

use App\Http\Requests\JsonFormRequest;
use App\Services\CurrentOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class DivisionsFromTemplatesRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageDivisions', $this->route('event'));
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'template_ids' => ['required', 'array', 'min:1'],
            'template_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('division_templates', 'id')->where('organization_id', app(CurrentOrganization::class)->id()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['template_ids.required' => 'Pilih minimal satu template seksi.', 'template_ids.min' => 'Pilih minimal satu template seksi.'];
    }
}
