<?php

namespace App\Actions\Work;

use App\Enums\MembershipStatus;
use App\Enums\TaskStatus;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkProgram;
use App\Services\AuditLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveTask
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Create or update a task and its collaborators.
     *
     * A subtask always sits in its parent's division and program, and subtasks go one level
     * deep. A task in a program sits in the program's division. Owner and collaborators must
     * be on the event committee. A new task starts open.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<int>|null  $assigneeIds  null leaves the collaborators untouched
     *
     * @throws ValidationException
     */
    public function handle(Event $event, Task $task, array $attributes, ?array $assigneeIds, User $actor): Task
    {
        return DB::transaction(function () use ($event, $task, $attributes, $assigneeIds, $actor): Task {
            $isNew = ! $task->exists;
            $task->event_id = $event->getKey();
            $task->fill($attributes);

            $this->placeInHierarchy($event, $task);
            $assigneeIds = $assigneeIds === null ? null : $this->committeeMembers($event, array_values(array_diff($assigneeIds, [$task->owner_membership_id])), 'assignee_ids');

            if ($task->owner_membership_id !== null) {
                $this->committeeMembers($event, [$task->owner_membership_id], 'owner_membership_id');
            }

            if ($isNew) {
                $task->status = TaskStatus::Todo;
            }

            $changed = $task->getDirty();
            $before = $isNew ? [] : Arr::only($task->getRawOriginal(), array_keys($changed));
            $assigneesBefore = $isNew ? [] : $task->assignees()->pluck('event_memberships.id')->sort()->values()->all();

            $task->save();

            if ($assigneeIds !== null) {
                $task->assignees()->sync(array_fill_keys($assigneeIds, ['event_id' => $event->getKey()]));
            }

            $assigneesAfter = $assigneeIds === null ? $assigneesBefore : collect($assigneeIds)->sort()->values()->all();

            if (! $isNew) {
                $task->children()->update(['division_id' => $task->division_id, 'work_program_id' => $task->work_program_id]);
            }

            if ($isNew || $changed !== [] || $assigneesAfter !== $assigneesBefore) {
                $this->audit->record(
                    action: 'tasks.'.($isNew ? 'created' : 'updated'),
                    auditable: $task,
                    before: $isNew ? [] : [...$before, 'assignees' => $assigneesBefore],
                    after: [...$changed, 'assignees' => $assigneesAfter],
                    eventId: $event->getKey(),
                    organizationId: $event->organization_id,
                    actor: $actor,
                );
            }

            return $task;
        });
    }

    /**
     * @throws ValidationException
     */
    private function placeInHierarchy(Event $event, Task $task): void
    {
        if ($task->parent_id !== null) {
            $parent = $event->tasks()->findOrFail($task->parent_id);

            if ($parent->is($task) || $parent->parent_id !== null) {
                throw ValidationException::withMessages(['parent_id' => 'Tugas induk harus tugas utama, bukan subtugas atau tugas ini sendiri.']);
            }

            if ($task->exists && $task->children()->exists()) {
                throw ValidationException::withMessages(['parent_id' => 'Tugas yang memiliki subtugas tidak dapat dijadikan subtugas.']);
            }

            $task->division_id = $parent->division_id;
            $task->work_program_id = $parent->work_program_id;

            return;
        }

        if ($task->work_program_id !== null) {
            $task->division_id = WorkProgram::query()->where('event_id', $event->getKey())->findOrFail($task->work_program_id)->division_id;
        }
    }

    /**
     * @param  list<int>  $membershipIds
     * @return list<int>
     *
     * @throws ValidationException
     */
    private function committeeMembers(Event $event, array $membershipIds, string $field): array
    {
        $membershipIds = array_values(array_unique(array_map('intval', $membershipIds)));

        $found = $event->memberships()
            ->whereKey($membershipIds)
            ->whereIn('status', [MembershipStatus::Invited, MembershipStatus::Active])
            ->count();

        if ($found !== count($membershipIds)) {
            throw ValidationException::withMessages([$field => 'Penanggung jawab dan anggota tugas harus panitia aktif atau yang sedang diundang di event ini.']);
        }

        return $membershipIds;
    }
}
