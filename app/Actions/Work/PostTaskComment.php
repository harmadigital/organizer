<?php

namespace App\Actions\Work;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Services\CommitteeNotifier;

class PostTaskComment
{
    public function __construct(private CommitteeNotifier $notifier) {}

    /**
     * Post a comment; the people involved in the task are told.
     */
    public function handle(Task $task, User $author, string $body): TaskComment
    {
        $comment = $task->comments()->create([
            'event_id' => $task->event_id,
            'user_id' => $author->getKey(),
            'body' => trim($body),
        ]);

        $this->notifier->taskCommented($comment->setRelation('task', $task)->setRelation('author', $author));

        return $comment;
    }
}
