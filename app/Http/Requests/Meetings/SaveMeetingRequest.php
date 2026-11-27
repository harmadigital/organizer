<?php

namespace App\Http\Requests\Meetings;

use App\Http\Requests\JsonFormRequest;
use App\Models\Event;
use App\Models\Meeting;
use App\Services\MeetingAuthority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class SaveMeetingRequest extends JsonFormRequest
{
    /**
     * The person must run meetings where the meeting is now and where it moves to. No
     * division means a core team meeting.
     */
    public function authorize(MeetingAuthority $authority): bool
    {
        $meeting = $this->route('meeting');

        if ($meeting instanceof Meeting && ! $authority->canManage($this->user(), $meeting)) {
            return false;
        }

        return $authority->canManageIn($this->user(), $this->event(), $this->filled('division_id') ? $this->integer('division_id') : null);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')->where('event_id', $this->event()->getKey())],
            'meets_on' => ['required', 'date'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:200'],
            'agenda' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ends_at.after' => 'Jam selesai harus setelah jam mulai.',
            'starts_at.date_format' => 'Pilih jam mulai, misalnya 19:00.',
            'ends_at.date_format' => 'Pilih jam selesai, misalnya 21:00.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'nama rapat',
            'division_id' => 'seksi',
            'meets_on' => 'tanggal',
            'starts_at' => 'jam mulai',
            'ends_at' => 'jam selesai',
            'location' => 'tempat',
            'agenda' => 'agenda',
        ];
    }

    private function event(): Event
    {
        return $this->route('event');
    }
}
