<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\MasterRecordRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class SaveDivisionTemplateRequest extends MasterRecordRequest
{
    protected function routeParameter(): string
    {
        return 'divisionTemplate';
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', $this->uniqueInOrganization('division_templates', 'name')],
            'description' => ['nullable', 'string', 'max:1000'],
            'default_responsibilities' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama template seksi',
            'description' => 'deskripsi',
            'default_responsibilities' => 'tanggung jawab bawaan',
            'is_active' => 'status',
        ];
    }
}
