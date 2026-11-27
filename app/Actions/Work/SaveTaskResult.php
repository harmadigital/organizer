<?php

namespace App\Actions\Work;

use App\Models\Task;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class SaveTaskResult
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Write or correct what the task produced.
     */
    public function handle(Task $task, string $result, User $actor): Task
    {
        $result = trim($result);

        if ($task->result === $result) {
            return $task;
        }

        return DB::transaction(function () use ($task, $result, $actor): Task {
            $before = $task->result;
            $task->result = $result;
            $task->save();

            $this->audit->record(
                action: 'tasks.result-saved',
                auditable: $task,
                before: $before === null ? [] : ['result' => $before],
                after: ['result' => $result],
                eventId: $task->event_id,
                organizationId: $task->event->organization_id,
                actor: $actor,
            );

            return $task;
        });
    }
}
