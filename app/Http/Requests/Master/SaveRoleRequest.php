<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\JsonFormRequest;
use App\Services\AccountAuthority;
use Database\Seeders\PermissionSeeder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Role;

/**
 * Saves an organization role. Nobody may grant a permission they do not hold
 * themselves, or edit a role that is stronger than their own permissions.
 */
class SaveRoleRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        $role = $this->targetRole();

        return $role === null || app(AccountAuthority::class)->canGrantRole($this->user(), $role);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->string('name')->squish()->value(),
            'permission' => array_values(array_filter((array) $this->input('permission', []), 'filled')),
        ]);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($this->targetRole()),
            ],
            'permission' => ['required', 'array', 'min:1'],
            'permission.*' => ['string', 'distinct', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->keepSystemRoleNames($validator),
            fn (Validator $validator) => $this->preventPermissionEscalation($validator),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Nama role ini sudah dipakai.',
            'permission.required' => 'Pilih minimal satu permission.',
            'permission.min' => 'Pilih minimal satu permission.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama role',
            'permission' => 'permission',
            'permission.*' => 'permission',
        ];
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return $this->validated('permission');
    }

    private function targetRole(): ?Role
    {
        $role = $this->route('role');

        return $role instanceof Role ? $role : null;
    }

    /**
     * The seeders find the built-in roles by name, so those names never change.
     */
    private function keepSystemRoleNames(Validator $validator): void
    {
        $role = $this->targetRole();

        if ($role !== null && in_array($role->name, PermissionSeeder::SYSTEM_ROLES, true) && $this->input('name') !== $role->name) {
            $validator->errors()->add('name', 'Nama role bawaan sistem tidak dapat diubah.');
        }
    }

    private function preventPermissionEscalation(Validator $validator): void
    {
        $notHeld = array_diff(
            (array) $this->input('permission', []),
            $this->user()->getAllPermissions()->pluck('name')->all(),
        );

        if ($notHeld !== []) {
            $validator->errors()->add('permission', 'Anda tidak dapat memberikan permission yang tidak Anda miliki: '.implode(', ', $notHeld).'.');
        }
    }
}
