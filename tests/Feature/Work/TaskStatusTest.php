<?php

namespace Tests\Feature\Work;

use App\Actions\Work\AddTaskDependency;
use App\Actions\Work\CompleteTask;
use App\Enums\CommitteeRoleKind;
use App\Enums\TaskStatus;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Document;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskAuthority;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

/**
 * The committee only records finished work: a task is open until someone marks it
 * finished with its result, optionally with photos or documents. There is no review.
 */
class TaskStatusTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    private Division $acara;

    private User $coordinator;

    private User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->setUpCommittees();
        $this->event = $this->makeEvent();
        $this->acara = $this->makeDivision($this->event, 'Seksi Acara');
        $this->coordinator = User::factory()->create();
        $this->seat($this->coordinator, $this->event, CommitteeRoleKind::DivisionCoordinator, $this->acara);
        $this->worker = User::factory()->create();
        $this->seat($this->worker, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function taskOfTheWorker(array $attributes = []): Task
    {
        return $this->makeTask($this->event, [
            'division_id' => $this->acara->id,
            'owner_membership_id' => $this->membershipOf($this->worker, $this->event)->id,
            ...$attributes,
        ]);
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    private function complete(User $actor, Task $task, ?string $result, array $files = [])
    {
        return $this->actingAs($actor)->post(
            route('events.tasks.complete', [$this->event, $task]),
            ['result' => $result, 'files' => $files],
            ['Accept' => 'application/json'],
        );
    }

    private function completeDirectly(Task $task, string $result = 'Selesai dikerjakan.'): void
    {
        $this->app->make(CompleteTask::class)->handle($task, $result, [], $this->organizer);
    }

    public function test_the_owner_marks_a_task_finished_with_its_result_and_photos(): void
    {
        $task = $this->taskOfTheWorker();

        $this->complete($this->worker, $task, 'Sound system terpasang dan sudah dicoba.', [
            UploadedFile::fake()->image('panggung.jpg'),
            UploadedFile::fake()->create('nota-sewa.pdf', 40, 'application/pdf'),
        ])->assertOk()->assertJsonPath('data.status', 'completed');

        $task->refresh();
        $this->assertSame(TaskStatus::Completed, $task->status);
        $this->assertSame('Sound system terpasang dan sudah dicoba.', $task->result);
        $this->assertNotNull($task->completed_at);
        $this->assertSame(['nota-sewa', 'panggung'], $task->documents()->orderBy('title')->pluck('title')->all());
        $this->assertSame([['complete', 'todo', 'completed']], $task->statusChanges->map(fn ($change) => [$change->action->value, $change->from_status->value, $change->to_status->value])->all());
        $this->assertSame('Sound system terpasang dan sudah dicoba.', AuditLog::query()->where('action', 'tasks.result-saved')->sole()->after['result']);
    }

    public function test_a_task_cannot_be_marked_finished_without_a_result(): void
    {
        $task = $this->taskOfTheWorker();

        $this->complete($this->worker, $task, '')
            ->assertStatus(422)
            ->assertJsonPath('errors.result.0', 'Tuliskan hasil pekerjaan sebelum menandai tugas selesai.');

        $this->assertSame(TaskStatus::Todo, $task->fresh()->status);
    }

    public function test_a_task_left_in_a_retired_status_can_still_be_finished(): void
    {
        $task = $this->taskOfTheWorker();
        $task->forceFill(['status' => TaskStatus::InProgress])->save();

        $this->complete($this->worker, $task, 'Beres.')->assertOk()->assertJsonPath('data.status', 'completed');
    }

    public function test_a_division_member_cannot_finish_a_task_they_do_not_work_on(): void
    {
        $task = $this->makeTask($this->event, ['division_id' => $this->acara->id]);

        $this->complete($this->worker, $task, 'Bukan tugas saya')->assertForbidden();

        $this->assertNull($task->fresh()->result);
    }

    public function test_a_collaborator_may_finish_the_task(): void
    {
        $task = $this->makeTask($this->event, ['division_id' => $this->acara->id], [$this->membershipOf($this->worker, $this->event)]);

        $this->complete($this->worker, $task, 'Dikerjakan bersama.')->assertOk();
    }

    public function test_a_task_cannot_be_finished_while_a_prerequisite_is_open_and_nothing_is_kept(): void
    {
        $task = $this->taskOfTheWorker();
        $prerequisite = $this->makeTask($this->event, ['title' => 'Tanda tangan kontrak', 'division_id' => $this->acara->id]);
        $this->app->make(AddTaskDependency::class)->handle($task, $prerequisite, $this->organizer);

        $this->complete($this->worker, $task, 'Sudah beres.', [UploadedFile::fake()->image('bukti.jpg')])
            ->assertStatus(422)
            ->assertJsonPath('errors.action.0', 'Tugas ini masih menunggu tugas lain selesai: Tanda tangan kontrak.');

        $this->assertNull($task->fresh()->result);
        $this->assertSame(0, Document::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());

        $this->completeDirectly($prerequisite);
        $this->complete($this->worker, $task, 'Sudah beres.')->assertOk();
    }

    public function test_a_parent_cannot_be_finished_while_a_subtask_is_open(): void
    {
        $parent = $this->taskOfTheWorker();
        $this->makeTask($this->event, ['parent_id' => $parent->id]);

        $this->complete($this->worker, $parent, 'Semua beres?')
            ->assertStatus(422)
            ->assertJsonPath('errors.action.0', 'Selesaikan atau batalkan semua subtugas terlebih dahulu.');

        $this->assertSame(TaskStatus::Todo, $parent->fresh()->status);
    }

    public function test_the_parent_page_offers_finishing_only_the_open_subtasks_the_person_works_on(): void
    {
        $workerMembership = $this->membershipOf($this->worker, $this->event);
        $parent = $this->makeTask($this->event, [
            'division_id' => $this->acara->id,
            'owner_membership_id' => $this->membershipOf($this->coordinator, $this->event)->id,
        ]);
        $owned = $this->makeTask($this->event, ['title' => 'Konfirmasi pembaca Alkitab', 'parent_id' => $parent->id, 'owner_membership_id' => $workerMembership->id]);
        $shared = $this->makeTask($this->event, ['title' => 'Konfirmasi kolektan', 'parent_id' => $parent->id], [$workerMembership]);
        $someoneElses = $this->makeTask($this->event, ['title' => 'Konfirmasi pemusik', 'parent_id' => $parent->id]);
        $finished = $this->makeTask($this->event, ['title' => 'Konfirmasi pemandu pujian', 'parent_id' => $parent->id, 'owner_membership_id' => $workerMembership->id]);
        $this->completeDirectly($finished);

        $page = $this->actingAs($this->worker)->get(route('events.tasks.detail', [$this->event, $parent]))->assertOk();

        $page->assertSee('data-url="'.route('events.tasks.complete', [$this->event, $owned]).'"', false);
        $page->assertSee('data-url="'.route('events.tasks.complete', [$this->event, $shared]).'"', false);
        $page->assertDontSee('data-url="'.route('events.tasks.complete', [$this->event, $someoneElses]).'"', false);
        $page->assertDontSee('data-url="'.route('events.tasks.complete', [$this->event, $finished]).'"', false);
    }

    public function test_a_finished_task_cannot_be_finished_again(): void
    {
        $task = $this->taskOfTheWorker();
        $this->completeDirectly($task);

        $this->assertFalse(app(TaskAuthority::class)->canComplete($this->worker, $task->fresh()));
        $this->complete($this->worker, $task, 'Lagi')
            ->assertStatus(422)
            ->assertJsonPath('errors.action.0', 'Tugas berstatus Selesai tidak dapat diproses dengan aksi "Tandai Selesai".');
    }

    public function test_finishing_needs_no_approval_even_for_a_task_once_marked_for_review(): void
    {
        $task = $this->taskOfTheWorker();
        $task->forceFill(['requires_review' => true])->save();

        $this->complete($this->worker, $task, 'Langsung selesai.')->assertOk()->assertJsonPath('data.status', 'completed');
    }

    public function test_the_retired_status_endpoint_is_gone(): void
    {
        $this->actingAs($this->coordinator)->postJson('/events/'.$this->event->id.'/tasks/'.$this->taskOfTheWorker()->id.'/status', ['action' => 'approve'])
            ->assertNotFound();
    }
}
