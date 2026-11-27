<?php

namespace App\Actions\Work;

use App\Models\Document;
use App\Models\Task;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class DetachTaskDocument
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Remove a file from the task. A document nothing else refers to is archived (soft
     * deleted); its stored versions stay for the audit trail.
     */
    public function handle(Task $task, Document $document, User $actor): void
    {
        DB::transaction(function () use ($task, $document, $actor): void {
            $task->documents()->detach($document->getKey());

            if (! $document->links()->exists()) {
                $document->delete();
            }

            $this->audit->record(
                action: 'documents.removed',
                auditable: $document,
                before: ['title' => $document->title, 'task_id' => $task->getKey()],
                eventId: $task->event_id,
                organizationId: $task->event->organization_id,
                actor: $actor,
            );
        });
    }
}
