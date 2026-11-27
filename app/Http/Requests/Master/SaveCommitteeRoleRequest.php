<?php

namespace App\Http\Requests\Master;

use App\Enums\CommitteeRoleScope;
use App\Enums\EventAbility;
use App\Http\Requests\MasterRecordRequest;
use App\Models\CommitteeRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class SaveCommitteeRoleRequest extends MasterRecordRequest
{
    protected function routeParameter(): string
    {
        return 'committeeRole';
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'is_required_default' => $this->boolean('is_required_default'),
            'is_singleton_default' => $this->boolean('is_singleton_default'),
            'abilities' => $this->input('abilities', []),
        ]);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', $this->uniqueInOrganization('committee_roles', 'name')],
            'scope' => [Rule::requiredIf(! $this->isBuiltInRole()), Rule::enum(CommitteeRoleScope::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_required_default' => ['boolean'],
            'is_singleton_default' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
            'abilities' => ['array'],
            'abilities.*' => ['string', 'distinct', Rule::enum(EventAbility::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama jabatan',
            'scope' => 'lingkup',
            'description' => 'deskripsi',
            'is_required_default' => 'wajib ada',
            'is_singleton_default' => 'satu pemegang utama',
            'sort_order' => 'urutan',
            'is_active' => 'status',
            'abilities' => 'hak akses',
            'abilities.*' => 'hak akses',
        ];
    }

    /**
     * The role's own attributes, without the abilities.
     *
     * @return array<string, mixed>
     */
    public function roleAttributes(): array
    {
        $attributes = $this->safe()->except('abilities');
        $attributes['sort_order'] ??= 0;

        return $attributes;
    }

    private function isBuiltInRole(): bool
    {
        $role = $this->record();

        return $role instanceof CommitteeRole && $role->kind->isBuiltIn();
    }
}
