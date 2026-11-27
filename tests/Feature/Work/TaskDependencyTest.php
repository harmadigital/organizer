<?php

namespace Tests\Feature\Work;

use App\Actions\Work\AddTaskDependency;
use App\Enums\CommitteeRoleKind;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

class TaskDependencyTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCommittees();
        $this->event = $this->makeEvent();
    }

    private function addPrerequisite(Task $task, Task $prerequisite)
    {
        return $this->actingAs($this->organizer)->postJson(route('events.tasks.dependencies.insert', [$this->event, $task]), ['depends_on_task_id' => $prerequisite->id]);
    }

    public function test_a_prerequisite_is_added_and_audited(): void
    {
        $task = $this->makeTask($this->event, ['title' => 'Cetak liturgi']);
        $prerequisite = $this->makeTask($this->event, ['title' => 'Finalisasi liturgi']);

        $this->addPrerequisite($task, $prerequisite)->assertCreated();

        $this->assertSame([$prerequisite->id], $task->dependencies()->pluck('tasks.id')->all());
        $this->assertSame('Finalisasi liturgi', AuditLog::query()->where('action', 'tasks.dependency-added')->sole()->after['depends_on']);
    }

    public function test_a_chain_that_leads_back_to_the_task_is_refused(): void
    {
        $first = $this->makeTask($this->event, ['title' => 'A']);
        $second = $this->makeTask($this->event, ['title' => 'B']);
        $third = $this->makeTask($this->event, ['title' => 'C']);
        $add = $this->app->make(AddTaskDependency::class);
        $add->handle($second, $first, $this->organizer);
        $add->handle($third, $second, $this->organizer);

        $this->addPrerequisite($first, $third)
            ->assertStatus(422)
            ->assertJsonPath('errors.depends_on_task_id.0', 'Prasyarat ini membuat rantai tugas berputar kembali ke tugas ini.');

        $this->assertSame(0, $first->dependencies()->count());
    }

    public function test_a_task_cannot_wait_for_itself_its_parent_or_its_subtask(): void
    {
        $parent = $this->makeTask($this->event, ['title' => 'Induk']);
        $subtask = $this->makeTask($this->event, ['title' => 'Anak', 'parent_id' => $parent->id]);

        $this->addPrerequisite($parent, $parent)->assertStatus(422)->assertJsonPath('errors.depends_on_task_id.0', 'Tugas tidak dapat menunggu dirinya sendiri.');
        $this->addPrerequisite($parent, $subtask)->assertStatus(422)->assertJsonPath('errors.depends_on_task_id.0', 'Tugas induk dan subtugasnya sudah saling terkait; tidak perlu dijadikan prasyarat.');
        $this->addPrerequisite($subtask, $parent)->assertStatus(422);
    }

    public function test_a_task_of_another_event_cannot_be_a_prerequisite(): void
    {
        $task = $this->makeTask($this->event);
        $foreign = $this->makeTask($this->makeEvent(['name' => 'Event Lain']));

        $this->addPrerequisite($task, $foreign)
            ->assertStatus(422)
            ->assertJsonPath('errors.depends_on_task_id.0', 'Tugas prasyarat harus berasal dari event yang sama.');

        $this->assertSame(0, $task->dependencies()->count());
    }

    public function test_the_same_prerequisite_is_not_added_twice(): void
    {
        $task = $this->makeTask($this->event);
        $prerequisite = $this->makeTask($this->event, ['title' => 'Lain']);
        $this->addPrerequisite($task, $prerequisite)->assertCreated();

        $this->addPrerequisite($task, $prerequisite)->assertStatus(422)->assertJsonPath('errors.depends_on_task_id.0', 'Tugas ini sudah menjadi prasyarat.');
    }

    public function test_only_task_managers_change_prerequisites(): void
    {
        $division = $this->makeDivision($this->event);
        $member = User::factory()->create();
        $this->seat($member, $this->event, CommitteeRoleKind::DivisionMember, $division);
        $task = $this->makeTask($this->event, ['division_id' => $division->id, 'owner_membership_id' => $this->membershipOf($member, $this->event)->id]);
        $prerequisite = $this->makeTask($this->event, ['title' => 'Lain', 'division_id' => $division->id]);

        $this->actingAs($member)->postJson(route('events.tasks.dependencies.insert', [$this->event, $task]), ['depends_on_task_id' => $prerequisite->id])->assertForbidden();
        $this->app->make(AddTaskDependency::class)->handle($task, $prerequisite, $this->organizer);
        $this->actingAs($member)->postJson(route('events.tasks.dependencies.delete', [$this->event, $task, $prerequisite]))->assertForbidden();

        $this->assertSame(1, $task->dependencies()->count());
    }

    public function test_a_prerequisite_can_be_removed(): void
    {
        $task = $this->makeTask($this->event);
        $prerequisite = $this->makeTask($this->event, ['title' => 'Lain']);
        $this->app->make(AddTaskDependency::class)->handle($task, $prerequisite, $this->organizer);

        $this->actingAs($this->organizer)->postJson(route('events.tasks.dependencies.delete', [$this->event, $task, $prerequisite]))->assertOk();

        $this->assertSame(0, $task->dependencies()->count());
        $this->assertSame(1, AuditLog::query()->where('action', 'tasks.dependency-removed')->count());
    }
}
