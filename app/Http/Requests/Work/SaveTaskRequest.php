<?php

namespace App\Http\Requests\Work;

use App\Enums\TaskPriority;
use App\Enums\TaskSize;
use App\Http\Requests\JsonFormRequest;
use App\Models\Event;
use App\Models\Task;
use App\Models\WorkProgram;
use App\Services\TaskAuthority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class SaveTaskRequest extends JsonFormRequest
{
    /**
     * The person must manage tasks where the task is now and where it ends up: a subtask
     * lives in its parent's division, a program task in the program's division. A core team
     * member who may only add tasks for themselves may do so for core team tasks they own.
     */
    public function authorize(TaskAuthority $authority): bool
    {
        $task = $this->task();

        if ($task !== null && ! $authority->canManage($this->user(), $task)) {
            return false;
        }

        $divisionId = $this->targetDivisionId();

        return $authority->canManageTasksIn($this->user(), $this->event(), $divisionId)
            || $authority->mayCreateAsOwnTask($this->user(), $this->event(), $divisionId, $this->filled('owner_membership_id') ? $this->integer('owner_membership_id') : null);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $eventId = $this->event()->getKey();
        $member = Rule::exists('event_memberships', 'id')->where('event_id', $eventId)->whereIn('status', ['invited', 'active']);

        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')->where('event_id', $eventId)],
            'work_program_id' => ['nullable', 'integer', Rule::exists('work_programs', 'id')->where('event_id', $eventId)],
            'parent_id' => ['nullable', 'integer', Rule::exists('tasks', 'id')->where('event_id', $eventId)->whereNull('parent_id')],
            'owner_membership_id' => ['nullable', 'integer', $member],
            'assignee_ids' => ['nullable', 'array', 'max:20'],
            'assignee_ids.*' => ['integer', 'distinct', $member],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'starts_on' => ['nullable', 'date'],
            'due_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'weight' => ['required', 'integer', Rule::in(TaskSize::values())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'due_on.after_or_equal' => 'Tenggat tidak boleh sebelum tanggal mulai.',
            'weight.in' => 'Pilih besar pekerjaan: ringan, sedang, atau berat.',
            'weight.required' => 'Pilih besar pekerjaan: ringan, sedang, atau berat.',
            'parent_id.exists' => 'Tugas induk harus tugas utama di event ini.',
            'owner_membership_id.exists' => 'Penanggung jawab harus panitia aktif atau yang sedang diundang di event ini.',
            'assignee_ids.*.exists' => 'Anggota tugas harus panitia aktif atau yang sedang diundang di event ini.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul tugas',
            'description' => 'deskripsi',
            'division_id' => 'seksi',
            'work_program_id' => 'program kerja',
            'parent_id' => 'tugas induk',
            'owner_membership_id' => 'penanggung jawab',
            'assignee_ids' => 'anggota tugas',
            'priority' => 'prioritas',
            'starts_on' => 'tanggal mulai',
            'due_on' => 'tenggat',
            'weight' => 'besar pekerjaan',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function taskAttributes(): array
    {
        $attributes = collect($this->validated())->except(['assignee_ids'])->all();

        return $attributes;
    }

    /**
     * @return list<int>
     */
    public function assigneeIds(): array
    {
        return array_map('intval', $this->validated('assignee_ids') ?? []);
    }

    private function targetDivisionId(): ?int
    {
        $eventId = $this->event()->getKey();

        if ($this->filled('parent_id')) {
            return Task::query()->where('event_id', $eventId)->whereKey($this->integer('parent_id'))->value('division_id');
        }

        if ($this->filled('work_program_id')) {
            return WorkProgram::query()->where('event_id', $eventId)->whereKey($this->integer('work_program_id'))->value('division_id');
        }

        return $this->filled('division_id') ? $this->integer('division_id') : null;
    }

    private function event(): Event
    {
        return $this->route('event');
    }

    private function task(): ?Task
    {
        $task = $this->route('task');

        return $task instanceof Task ? $task : null;
    }
}
