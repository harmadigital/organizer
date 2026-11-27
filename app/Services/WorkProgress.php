<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Progress of a set of tasks, by weight.
 *
 * Only leaf tasks count, so a parent and its subtasks are never counted twice: a task is a
 * leaf when none of its subtasks is still in play. Cancelled tasks and drafts do not count.
 * Progress is the weight of finished leaves divided by the weight of counted leaves; with
 * nothing to count the result is null, shown as "N/A".
 */
class WorkProgress
{
    /**
     * @param  Collection<int, Task>  $tasks  every task of the event, so parents can see their subtasks
     * @return array{percent: ?int, done: int, total: int}
     */
    public function of(Collection $tasks, ?callable $filter = null): array
    {
        $parentIdsInPlay = $tasks
            ->filter(fn (Task $task): bool => $this->counts($task) && $task->parent_id !== null)
            ->pluck('parent_id')
            ->flip();

        $leaves = $tasks->filter(fn (Task $task): bool => $this->counts($task) && ! $parentIdsInPlay->has($task->getKey()));

        if ($filter !== null) {
            $leaves = $leaves->filter($filter);
        }

        $total = (int) $leaves->sum('weight');
        $done = (int) $leaves->filter(fn (Task $task): bool => $task->status === TaskStatus::Completed)->sum('weight');

        return [
            'percent' => $total === 0 ? null : (int) floor($done * 100 / $total),
            'done' => $done,
            'total' => $total,
        ];
    }

    /**
     * Progress of each group, keyed by the value the callback returns for a task.
     *
     * @param  Collection<int, Task>  $tasks
     * @return array<int|string, array{percent: ?int, done: int, total: int}>
     */
    public function groupedBy(Collection $tasks, string $attribute): array
    {
        $progress = [];

        foreach ($tasks->pluck($attribute)->filter()->unique() as $key) {
            $progress[$key] = $this->of($tasks, fn (Task $task): bool => $task->getAttribute($attribute) === $key);
        }

        return $progress;
    }

    private function counts(Task $task): bool
    {
        return $task->status !== TaskStatus::Cancelled && $task->status !== TaskStatus::Draft;
    }
}
