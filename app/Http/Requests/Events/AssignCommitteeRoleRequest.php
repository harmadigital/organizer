<?php

namespace App\Http\Requests\Events;

use App\Enums\UserStatus;
use App\Http\Requests\JsonFormRequest;
use App\Models\CommitteeRole;
use App\Models\Division;
use App\Models\Event;
use App\Services\CommitteeAuthority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class AssignCommitteeRoleRequest extends JsonFormRequest
{
    /**
     * Whether the actor may hand out this role here. Unknown roles or divisions pass,
     * so validation can explain the problem instead of a bare "forbidden".
     */
    public function authorize(): bool
    {
        $role = $this->committeeRole();

        if ($role === null) {
            return $this->user()->can('view', $this->event());
        }

        return app(CommitteeAuthority::class)->canAssign($this->user(), $this->event(), $role, $this->division());
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_primary' => $this->boolean('is_primary'),
            'confirm_now' => $this->boolean('confirm_now'),
            'division_id' => $this->filled('division_id') ? $this->input('division_id') : null,
        ]);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('status', UserStatus::Active->value)],
            'committee_role_id' => [
                'required',
                'integer',
                Rule::exists('committee_roles', 'id')->where('organization_id', $this->event()->organization_id)->where('is_active', true),
            ],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')->where('event_id', $this->event()->getKey())],
            'is_primary' => ['boolean'],
            'confirm_now' => ['boolean'],
            'job_description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['user_id.exists' => 'User tidak ditemukan atau sudah nonaktif.'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'orang',
            'committee_role_id' => 'jabatan',
            'division_id' => 'seksi',
            'job_description' => 'uraian tugas',
        ];
    }

    public function event(): Event
    {
        return $this->route('event');
    }

    public function committeeRole(): ?CommitteeRole
    {
        return CommitteeRole::query()
            ->forOrganization($this->event()->organization_id)
            ->find($this->integer('committee_role_id'));
    }

    public function division(): ?Division
    {
        return $this->filled('division_id') ? $this->event()->divisions()->find($this->integer('division_id')) : null;
    }
}
