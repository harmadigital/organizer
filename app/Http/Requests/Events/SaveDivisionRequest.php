<?php

namespace App\Http\Requests\Events;

use App\Http\Requests\JsonFormRequest;
use App\Models\Division;
use App\Models\Event;
use App\Services\CurrentOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class SaveDivisionRequest extends JsonFormRequest
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
        /** @var Event $event */
        $event = $this->route('event');
        $division = $this->route('division');
        $division = $division instanceof Division ? $division : null;

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('divisions', 'name')->where('event_id', $event->getKey())->ignore($division)],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('divisions', 'id')->where('event_id', $event->getKey()),
                Rule::notIn($division === null ? [] : [$division->getKey(), ...$division->descendantIds()]),
            ],
            'division_template_id' => [
                'nullable',
                'integer',
                Rule::exists('division_templates', 'id')->where('organization_id', app(CurrentOrganization::class)->id()),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'responsibilities' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Nama seksi ini sudah dipakai pada event ini.',
            'parent_id.not_in' => 'Seksi induk tidak boleh seksi itu sendiri atau seksi di bawahnya.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama seksi',
            'parent_id' => 'seksi induk',
            'division_template_id' => 'template seksi',
            'description' => 'deskripsi',
            'responsibilities' => 'tanggung jawab',
            'sort_order' => 'urutan',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function divisionAttributes(): array
    {
        $attributes = $this->validated();
        $attributes['sort_order'] ??= 0;

        return $attributes;
    }
}
