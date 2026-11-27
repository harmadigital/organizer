<?php

namespace App\Actions\Work;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;

class PostTaskComment
{
    public function handle(Task $task, User $author, string $body): TaskComment
    {
        return $task->comments()->create([
            'event_id' => $task->event_id,
            'user_id' => $author->getKey(),
            'body' => trim($body),
        ]);
    }
}
