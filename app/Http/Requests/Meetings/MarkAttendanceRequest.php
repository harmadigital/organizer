<?php

namespace App\Http\Requests\Meetings;

use App\Http\Requests\JsonFormRequest;
use App\Services\MeetingAuthority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class MarkAttendanceRequest extends JsonFormRequest
{
    public function authorize(MeetingAuthority $authority): bool
    {
        return $authority->canManage($this->user(), $this->route('meeting'));
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'membership_id' => ['required', 'integer', Rule::exists('event_memberships', 'id')->where('event_id', $this->route('event')->getKey())->whereIn('status', ['invited', 'active'])],
            'present' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['membership_id.exists' => 'Orang ini bukan panitia event ini.'];
    }
}
