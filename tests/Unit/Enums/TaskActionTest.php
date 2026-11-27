<?php

namespace Tests\Unit\Enums;

use App\Enums\TaskAction;
use App\Enums\TaskStatus;
use PHPUnit\Framework\TestCase;

class TaskActionTest extends TestCase
{
    public function test_finishing_an_open_task_completes_it_directly(): void
    {
        $this->assertSame(TaskStatus::Completed, TaskAction::Complete->to());
        $this->assertContains(TaskStatus::Todo, TaskAction::Complete->startsFrom());
        $this->assertNotContains(TaskStatus::Completed, TaskAction::Complete->startsFrom());
    }

    public function test_an_open_task_left_in_a_retired_status_can_still_be_finished(): void
    {
        foreach ([TaskStatus::Draft, TaskStatus::InProgress, TaskStatus::Blocked] as $retired) {
            $this->assertContains($retired, TaskAction::Complete->startsFrom());
        }
    }

    public function test_finishing_is_the_only_action_left(): void
    {
        $available = array_values(array_filter(TaskAction::cases(), fn (TaskAction $action) => $action->startsFrom() !== []));

        $this->assertSame([TaskAction::Complete], $available);
    }

    public function test_a_task_is_either_open_or_finished(): void
    {
        $inUse = array_values(array_filter(TaskStatus::cases(), fn (TaskStatus $status) => ! $status->isRetired()));

        $this->assertSame([TaskStatus::Todo, TaskStatus::Completed], $inUse);
    }
}
