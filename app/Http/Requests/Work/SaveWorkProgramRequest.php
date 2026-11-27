<?php

namespace App\Http\Requests\Work;

use App\Enums\WorkProgramStatus;
use App\Http\Requests\JsonFormRequest;
use App\Models\Event;
use App\Models\WorkProgram;
use App\Services\TaskAuthority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class SaveWorkProgramRequest extends JsonFormRequest
{
    /**
     * The person must manage programs where the program is now and where it moves to. No
     * division means the core team.
     */
    public function authorize(TaskAuthority $authority): bool
    {
        $program = $this->program();

        if ($program !== null && ! $authority->canManageProgram($this->user(), $program)) {
            return false;
        }

        $divisionId = $this->filled('division_id') ? $this->integer('division_id') : null;

        return $authority->canManageProgramsIn($this->user(), $this->event(), $divisionId);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $eventId = $this->event()->getKey();

        return [
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')->where('event_id', $eventId)],
            'title' => ['required', 'string', 'max:200'],
            'objective' => ['nullable', 'string', 'max:2000'],
            'output' => ['nullable', 'string', 'max:2000'],
            'indicator' => ['nullable', 'string', 'max:2000'],
            'pic_membership_id' => ['nullable', 'integer', Rule::exists('event_memberships', 'id')->where('event_id', $eventId)->whereIn('status', ['invited', 'active'])],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'status' => ['required', Rule::enum(WorkProgramStatus::class)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ends_on.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'pic_membership_id.exists' => 'Penanggung jawab harus panitia aktif atau yang sedang diundang di event ini.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'division_id' => 'seksi',
            'title' => 'nama program',
            'objective' => 'tujuan',
            'output' => 'keluaran',
            'indicator' => 'indikator keberhasilan',
            'pic_membership_id' => 'penanggung jawab',
            'starts_on' => 'tanggal mulai',
            'ends_on' => 'tanggal selesai',
            'status' => 'status',
            'sort_order' => 'urutan',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function programAttributes(): array
    {
        $attributes = $this->validated();
        $attributes['sort_order'] ??= 0;

        return $attributes;
    }

    private function event(): Event
    {
        return $this->route('event');
    }

    private function program(): ?WorkProgram
    {
        $program = $this->route('program');

        return $program instanceof WorkProgram ? $program : null;
    }
}
