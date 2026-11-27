<?php

namespace App\Actions\Work;

use App\Enums\TaskAction;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only way a task's status changes. Who may take the action is checked by the caller
 * through TaskAuthority; the rules of the work itself are checked here.
 */
class ChangeTaskStatus
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(Task $task, TaskAction $action, ?string $note, User $actor): Task
    {
        $note = filled($note) ? trim($note) : null;

        return DB::transaction(function () use ($task, $action, $note, $actor): Task {
            // Read the status under a lock so two people acting at once cannot both move it.
            $task->status = Task::query()->whereKey($task->getKey())->lockForUpdate()->value('status');
            $task->syncOriginalAttribute('status');

            if (! in_array($task->status, $action->startsFrom(), true)) {
                throw ValidationException::withMessages([
                    'action' => "Tugas berstatus {$task->status->label()} tidak dapat diproses dengan aksi \"{$action->label()}\".",
                ]);
            }

            $to = $action->to();

            $this->ensureTheWorkAllows($task, $action, $to);

            $from = $task->status;
            $task->status = $to;
            $task->completed_at = $to === TaskStatus::Completed ? now() : null;
            $task->save();

            $task->statusChanges()->create([
                'action' => $action,
                'from_status' => $from,
                'to_status' => $to,
                'note' => $note,
                'actor_user_id' => $actor->getKey(),
            ]);

            $this->audit->record(
                action: 'tasks.status-changed',
                auditable: $task,
                before: ['status' => $from->value],
                after: ['status' => $to->value, 'action' => $action->value],
                reason: $note,
                eventId: $task->event_id,
                organizationId: $task->event->organization_id,
                actor: $actor,
            );

            return $task;
        });
    }

    /**
     * @throws ValidationException
     */
    private function ensureTheWorkAllows(Task $task, TaskAction $action, TaskStatus $to): void
    {
        if ($action === TaskAction::Complete && blank($task->result)) {
            throw ValidationException::withMessages(['result' => 'Tuliskan hasil pekerjaan sebelum menandai tugas selesai.']);
        }

        if ($to === TaskStatus::Completed) {
            $waitingOn = $task->dependencies()->whereNotIn('tasks.status', [TaskStatus::Completed, TaskStatus::Cancelled])->pluck('title');

            if ($waitingOn->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'action' => 'Tugas ini masih menunggu tugas lain selesai: '.$waitingOn->implode(', ').'.',
                ]);
            }
        }

        if ($to === TaskStatus::Completed
            && $task->children()->open()->exists()) {
            throw ValidationException::withMessages([
                'action' => 'Selesaikan atau batalkan semua subtugas terlebih dahulu.',
            ]);
        }
    }
}
