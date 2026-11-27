<?php

namespace App\Actions\Work;

use App\Models\Task;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class RemoveTaskDependency
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Task $task, Task $prerequisite, User $actor): void
    {
        DB::transaction(function () use ($task, $prerequisite, $actor): void {
            $task->dependencies()->detach($prerequisite->getKey());

            $this->audit->record(
                action: 'tasks.dependency-removed',
                auditable: $task,
                before: ['depends_on_task_id' => $prerequisite->getKey(), 'depends_on' => $prerequisite->title],
                eventId: $task->event_id,
                organizationId: $task->event->organization_id,
                actor: $actor,
            );
        });
    }
}
