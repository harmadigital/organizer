<?php

namespace App\Actions\Work;

use App\Enums\TaskAction;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Mark a task finished in one step: its result, the photos or documents that show it,
 * and the status change (to review when the task needs one). Nothing is kept when any
 * part fails.
 */
class CompleteTask
{
    public function __construct(
        private SaveTaskResult $saveResult,
        private AttachTaskDocument $attach,
        private ChangeTaskStatus $changeStatus,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     */
    public function handle(Task $task, string $result, array $files, User $actor): Task
    {
        return DB::transaction(function () use ($task, $result, $files, $actor): Task {
            $this->saveResult->handle($task, $result, $actor);
            $completed = $this->changeStatus->handle($task, TaskAction::Complete, null, $actor);

            // Last, so a refused status change never leaves stored files behind.
            $this->attach->handleMany($task, $files, $actor);

            return $completed;
        });
    }
}
