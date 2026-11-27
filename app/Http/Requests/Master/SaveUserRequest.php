<?php

namespace App\Http\Requests\Master;

use App\Enums\UserStatus;
use App\Http\Requests\JsonFormRequest;
use App\Models\User;
use App\Services\AccountAuthority;
use App\Services\CurrentOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Role;

/**
 * Saves a person. Login access (username, password, role) is part of the same form
 * but needs its own permission, and may only be changed on accounts that are not
 * more privileged than the person making the change.
 */
class SaveUserRequest extends JsonFormRequest
{
    /**
     * @var list<string>
     */
    public const ACCESS_FIELDS = ['login_enabled', 'username', 'password', 'password_confirmation', 'role'];

    /**
     * @var list<string>
     */
    private const DIRECTORY_FIELDS = ['name', 'email', 'phone', 'organization_unit_id', 'status', 'notes'];

    public function authorize(): bool
    {
        if (! $this->changesAccess()) {
            return true;
        }

        $target = $this->targetUser();

        return $this->user()->can('users.manage-access')
            && ($target === null || app(AccountAuthority::class)->canManage($this->user(), $target));
    }

    /**
     * Whether the form carries the login access section.
     */
    public function changesAccess(): bool
    {
        return $this->hasAny(self::ACCESS_FIELDS);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->filled('email') ? mb_strtolower($this->string('email')->trim()->value()) : null,
            'phone' => $this->filled('phone') ? preg_replace('/[\s().-]+/', '', $this->string('phone')->value()) : null,
        ]);

        if ($this->changesAccess()) {
            $this->merge([
                'login_enabled' => $this->boolean('login_enabled'),
                'username' => $this->filled('username') ? mb_strtolower($this->string('username')->trim()->value()) : null,
                'role' => $this->filled('role') ? $this->string('role')->value() : null,
            ]);
        }
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $target = $this->targetUser();

        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target)],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9]{8,15}$/', Rule::unique('users', 'phone')->ignore($target)],
            'organization_unit_id' => [
                'nullable',
                'integer',
                Rule::exists('organization_units', 'id')->where('organization_id', app(CurrentOrganization::class)->id()),
            ],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if (! $this->changesAccess()) {
            return $rules;
        }

        $loginEnabled = $this->boolean('login_enabled');

        return [
            ...$rules,
            'login_enabled' => ['boolean'],
            'username' => [
                Rule::requiredIf($loginEnabled),
                'nullable',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($target),
            ],
            'password' => [
                Rule::requiredIf($loginEnabled && $target?->getAuthPassword() === null),
                'nullable',
                'string',
                Password::default(),
                'confirmed',
            ],
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->protectOwnAccount($validator),
            fn (Validator $validator) => $this->preventRoleEscalation($validator),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah dipakai user lain.',
            'phone.unique' => 'Nomor telepon ini sudah dipakai user lain.',
            'phone.regex' => 'Nomor telepon harus terdiri dari 8 sampai 15 angka, boleh diawali tanda +.',
            'username.required' => 'Nama pengguna wajib diisi bila akses login diaktifkan.',
            'username.regex' => 'Nama pengguna hanya boleh berisi huruf kecil, angka, titik, garis bawah, dan tanda hubung.',
            'username.unique' => 'Nama pengguna ini sudah dipakai user lain.',
            'password.required' => 'Kata sandi wajib diisi saat akses login pertama kali diaktifkan.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'email' => 'email',
            'phone' => 'nomor telepon',
            'organization_unit_id' => 'unit pelayanan',
            'status' => 'status',
            'notes' => 'catatan',
            'login_enabled' => 'akses login',
            'username' => 'nama pengguna',
            'password' => 'kata sandi',
            'role' => 'role',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function directory(): array
    {
        return $this->safe()->only(self::DIRECTORY_FIELDS);
    }

    /**
     * @return array{login_enabled: bool, username: ?string, password: ?string, role: ?string}|null
     */
    public function access(): ?array
    {
        if (! $this->changesAccess()) {
            return null;
        }

        return [
            'login_enabled' => (bool) $this->validated('login_enabled'),
            'username' => $this->validated('username'),
            'password' => $this->validated('password'),
            'role' => $this->validated('role'),
        ];
    }

    private function targetUser(): ?User
    {
        $user = $this->route('user');

        return $user instanceof User ? $user : null;
    }

    /**
     * Nobody can lock themselves out or change their own privileges.
     */
    private function protectOwnAccount(Validator $validator): void
    {
        $target = $this->targetUser();

        if ($target === null || ! $target->is($this->user())) {
            return;
        }

        if ($this->input('status') === UserStatus::Inactive->value) {
            $validator->errors()->add('status', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        if (! $this->changesAccess()) {
            return;
        }

        if (! $this->boolean('login_enabled')) {
            $validator->errors()->add('login_enabled', 'Anda tidak dapat mencabut akses login Anda sendiri.');
        } elseif ($this->input('role') !== $target->getRoleNames()->first()) {
            $validator->errors()->add('role', 'Anda tidak dapat mengubah role Anda sendiri.');
        }
    }

    private function preventRoleEscalation(Validator $validator): void
    {
        if (! $this->changesAccess() || ! $this->boolean('login_enabled') || blank($this->input('role'))) {
            return;
        }

        $role = Role::query()->where('guard_name', 'web')->where('name', $this->input('role'))->with('permissions:id,name')->first();

        if ($role === null || $role->name === $this->targetUser()?->getRoleNames()->first()) {
            return;
        }

        if (! app(AccountAuthority::class)->canGrantRole($this->user(), $role)) {
            $validator->errors()->add('role', 'Anda tidak dapat memberikan role yang memiliki hak akses melebihi hak akses Anda.');
        }
    }
}
