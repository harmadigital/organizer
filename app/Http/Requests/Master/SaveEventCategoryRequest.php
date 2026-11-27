<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\MasterRecordRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class SaveEventCategoryRequest extends MasterRecordRequest
{
    protected function routeParameter(): string
    {
        return 'eventCategory';
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', $this->uniqueInOrganization('event_categories', 'name')],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama kategori',
            'description' => 'deskripsi',
            'is_active' => 'status',
        ];
    }
}
