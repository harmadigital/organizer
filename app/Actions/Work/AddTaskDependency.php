<?php

namespace App\Actions\Work;

use App\Models\Task;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddTaskDependency
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Make a task wait for another task of the same event. A chain that leads back to the
     * task itself, or a link between a task and its own subtask, could never be finished.
     *
     * @throws ValidationException
     */
    public function handle(Task $task, Task $prerequisite, User $actor): void
    {
        $fail = fn (string $message) => throw ValidationException::withMessages(['depends_on_task_id' => $message]);

        if ($prerequisite->event_id !== $task->event_id) {
            $fail('Tugas prasyarat harus berasal dari event yang sama.');
        }

        if ($prerequisite->is($task)) {
            $fail('Tugas tidak dapat menunggu dirinya sendiri.');
        }

        if ($prerequisite->parent_id === $task->getKey() || $task->parent_id === $prerequisite->getKey()) {
            $fail('Tugas induk dan subtugasnya sudah saling terkait; tidak perlu dijadikan prasyarat.');
        }

        if ($task->dependencies()->whereKey($prerequisite->getKey())->exists()) {
            $fail('Tugas ini sudah menjadi prasyarat.');
        }

        if ($this->leadsTo($prerequisite, $task)) {
            $fail('Prasyarat ini membuat rantai tugas berputar kembali ke tugas ini.');
        }

        DB::transaction(function () use ($task, $prerequisite, $actor): void {
            $task->dependencies()->attach($prerequisite->getKey(), ['event_id' => $task->event_id, 'created_by' => $actor->getKey()]);

            $this->audit->record(
                action: 'tasks.dependency-added',
                auditable: $task,
                after: ['depends_on_task_id' => $prerequisite->getKey(), 'depends_on' => $prerequisite->title],
                eventId: $task->event_id,
                organizationId: $task->event->organization_id,
                actor: $actor,
            );
        });
    }

    /**
     * Whether following prerequisites from the start reaches the target.
     */
    private function leadsTo(Task $start, Task $target): bool
    {
        $visited = [];
        $pending = [$start->getKey()];

        while ($pending !== []) {
            $visited = [...$visited, ...$pending];

            $pending = DB::table('task_dependencies')
                ->whereIn('task_id', $pending)
                ->pluck('depends_on_task_id')
                ->map(fn ($id) => (int) $id)
                ->diff($visited)
                ->values()
                ->all();

            if (in_array($target->getKey(), $pending, true)) {
                return true;
            }
        }

        return false;
    }
}
