<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\MasterRecordRequest;
use App\Models\OrganizationUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class SaveOrganizationUnitRequest extends MasterRecordRequest
{
    protected function routeParameter(): string
    {
        return 'organizationUnit';
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', $this->uniqueInOrganization('organization_units', 'name')],
            'parent_id' => [
                'nullable',
                'integer',
                $this->existsInOrganization('organization_units'),
                Rule::notIn($this->unitsThatCannotBeParent()),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parent_id.not_in' => 'Unit induk tidak boleh unit itu sendiri atau unit di bawahnya.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama unit',
            'parent_id' => 'unit induk',
            'description' => 'deskripsi',
            'is_active' => 'status',
        ];
    }

    /**
     * A unit placed under itself or under one of its own descendants would form a cycle.
     *
     * @return list<int>
     */
    private function unitsThatCannotBeParent(): array
    {
        $unit = $this->record();

        return $unit instanceof OrganizationUnit ? [$unit->getKey(), ...$unit->descendantIds()] : [];
    }
}
