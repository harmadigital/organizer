<?php

namespace App\Http\Requests\Events;

use App\Http\Requests\JsonFormRequest;
use App\Models\EventRoleAssignment;
use App\Services\CommitteeAuthority;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateAssignmentRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        /** @var EventRoleAssignment $assignment */
        $assignment = $this->route('assignment');

        return $assignment->isActive()
            && app(CommitteeAuthority::class)->canAssign($this->user(), $this->route('event'), $assignment->committeeRole, $assignment->division);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_primary' => $this->boolean('is_primary')]);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'job_description' => ['nullable', 'string', 'max:2000'],
            'is_primary' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['job_description' => 'uraian tugas'];
    }
}
