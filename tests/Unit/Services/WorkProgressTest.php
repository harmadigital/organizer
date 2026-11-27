<?php

namespace Tests\Unit\Services;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\WorkProgress;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class WorkProgressTest extends TestCase
{
    private function task(int $id, TaskStatus $status, int $weight = 1, ?int $parentId = null, ?int $programId = null): Task
    {
        return (new Task)->forceFill([
            'id' => $id,
            'status' => $status,
            'weight' => $weight,
            'parent_id' => $parentId,
            'work_program_id' => $programId,
        ]);
    }

    /**
     * @param  list<Task>  $tasks
     * @return array{percent: ?int, done: int, total: int}
     */
    private function progressOf(array $tasks): array
    {
        return (new WorkProgress)->of(new Collection($tasks));
    }

    public function test_progress_is_the_weight_of_finished_tasks_over_the_weight_of_counted_tasks(): void
    {
        $progress = $this->progressOf([
            $this->task(1, TaskStatus::Completed, weight: 3),
            $this->task(2, TaskStatus::Todo, weight: 1),
        ]);

        $this->assertSame(['percent' => 75, 'done' => 3, 'total' => 4], $progress);
    }

    public function test_a_parent_is_represented_by_its_subtasks_and_never_counted_twice(): void
    {
        $progress = $this->progressOf([
            $this->task(1, TaskStatus::Todo, weight: 10),
            $this->task(2, TaskStatus::Completed, weight: 1, parentId: 1),
            $this->task(3, TaskStatus::Todo, weight: 1, parentId: 1),
        ]);

        $this->assertSame(['percent' => 50, 'done' => 1, 'total' => 2], $progress);
    }

    public function test_cancelled_tasks_and_drafts_are_left_out(): void
    {
        $progress = $this->progressOf([
            $this->task(1, TaskStatus::Completed),
            $this->task(2, TaskStatus::Cancelled, weight: 5),
            $this->task(3, TaskStatus::Draft, weight: 5),
        ]);

        $this->assertSame(['percent' => 100, 'done' => 1, 'total' => 1], $progress);
    }

    public function test_a_parent_whose_subtasks_were_all_cancelled_counts_on_its_own(): void
    {
        $progress = $this->progressOf([
            $this->task(1, TaskStatus::Completed, weight: 2),
            $this->task(2, TaskStatus::Cancelled, parentId: 1),
        ]);

        $this->assertSame(['percent' => 100, 'done' => 2, 'total' => 2], $progress);
    }

    public function test_nothing_to_count_gives_no_percentage(): void
    {
        $this->assertSame(['percent' => null, 'done' => 0, 'total' => 0], $this->progressOf([]));
        $this->assertSame(['percent' => null, 'done' => 0, 'total' => 0], $this->progressOf([$this->task(1, TaskStatus::Cancelled)]));
    }

    public function test_progress_per_group_only_counts_the_tasks_of_that_group(): void
    {
        $progress = (new WorkProgress)->groupedBy(new Collection([
            $this->task(1, TaskStatus::Completed, programId: 7),
            $this->task(2, TaskStatus::Todo, programId: 7),
            $this->task(3, TaskStatus::Completed, programId: 8),
            $this->task(4, TaskStatus::Todo),
        ]), 'work_program_id');

        $this->assertSame([
            7 => ['percent' => 50, 'done' => 1, 'total' => 2],
            8 => ['percent' => 100, 'done' => 1, 'total' => 1],
        ], $progress);
    }
}
