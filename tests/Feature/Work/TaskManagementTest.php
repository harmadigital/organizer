<?php

namespace Tests\Feature\Work;

use App\Enums\CommitteeRoleKind;
use App\Enums\TaskStatus;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    private Division $acara;

    private User $coordinator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCommittees();
        $this->event = $this->makeEvent();
        $this->acara = $this->makeDivision($this->event, 'Seksi Acara');
        $this->coordinator = User::factory()->create();
        $this->seat($this->coordinator, $this->event, CommitteeRoleKind::DivisionCoordinator, $this->acara);
    }

    public function test_a_division_coordinator_creates_a_task_in_their_division_and_it_is_audited(): void
    {
        $member = User::factory()->create();
        $this->seat($member, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);

        $this->actingAs($this->coordinator)->postJson(route('events.tasks.insert', $this->event), [
            'title' => 'Susun rundown ibadah',
            'division_id' => $this->acara->id,
            'owner_membership_id' => $this->membershipOf($this->coordinator, $this->event)->id,
            'assignee_ids' => [$this->membershipOf($member, $this->event)->id],
            'priority' => 'high',
            'weight' => 3,
            'starts_on' => '2026-12-01',
            'due_on' => '2026-12-10',
        ])->assertCreated()->assertJsonPath('status', true);

        $task = Task::query()->where('title', 'Susun rundown ibadah')->sole();
        $this->assertSame(TaskStatus::Todo, $task->status);
        $this->assertSame($this->acara->id, $task->division_id);
        $this->assertSame([$this->membershipOf($member, $this->event)->id], $task->assignees()->pluck('event_memberships.id')->all());

        $entry = AuditLog::query()->where('action', 'tasks.created')->sole();
        $this->assertSame($this->event->id, $entry->event_id);
        $this->assertSame($this->coordinator->id, $entry->actor_user_id);
    }

    public function test_a_coordinator_cannot_create_tasks_in_another_division_or_for_the_whole_event(): void
    {
        $konsumsi = $this->makeDivision($this->event, 'Seksi Konsumsi');

        foreach ([$konsumsi->id, null] as $divisionId) {
            $this->actingAs($this->coordinator)->postJson(route('events.tasks.insert', $this->event), [
                'title' => 'Bukan seksinya', 'division_id' => $divisionId, 'priority' => 'medium', 'weight' => 1,
            ])->assertForbidden();
        }

        $this->assertSame(0, Task::query()->count());
    }

    public function test_a_division_member_may_not_create_tasks(): void
    {
        $member = User::factory()->create();
        $this->seat($member, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);

        $this->actingAs($member)->postJson(route('events.tasks.insert', $this->event), [
            'title' => 'Coba', 'division_id' => $this->acara->id, 'priority' => 'medium', 'weight' => 1,
        ])->assertForbidden();
    }

    public function test_the_treasurer_adds_and_changes_core_team_tasks_for_themselves_only(): void
    {
        $treasurer = User::factory()->create();
        $this->seat($treasurer, $this->event, CommitteeRoleKind::Treasurer);
        $own = $this->membershipOf($treasurer, $this->event)->id;
        $chair = User::factory()->create();
        $this->seat($chair, $this->event, CommitteeRoleKind::Chair);

        $this->actingAs($treasurer)->postJson(route('events.tasks.insert', $this->event), [
            'title' => 'Susun RAB awal', 'owner_membership_id' => $own, 'priority' => 'high', 'weight' => 3,
        ])->assertCreated();
        $this->actingAs($treasurer)->postJson(route('events.tasks.insert', $this->event), [
            'title' => 'Untuk ketua', 'owner_membership_id' => $this->membershipOf($chair, $this->event)->id, 'priority' => 'medium', 'weight' => 2,
        ])->assertForbidden();
        $this->actingAs($treasurer)->postJson(route('events.tasks.insert', $this->event), [
            'title' => 'Di seksi', 'division_id' => $this->acara->id, 'owner_membership_id' => $own, 'priority' => 'medium', 'weight' => 2,
        ])->assertForbidden();

        $mine = Task::query()->where('title', 'Susun RAB awal')->sole();
        $this->assertNull($mine->division_id);
        $this->actingAs($treasurer)->postJson(route('events.tasks.update', [$this->event, $mine]), [
            'title' => 'Susun RAB awal dan rencana pemasukan', 'owner_membership_id' => $own, 'priority' => 'high', 'weight' => 3,
        ])->assertOk();
        $this->actingAs($treasurer)->postJson(route('events.tasks.update', [$this->event, $mine]), [
            'title' => 'Dialihkan', 'owner_membership_id' => $this->membershipOf($chair, $this->event)->id, 'priority' => 'high', 'weight' => 3,
        ])->assertForbidden();

        $chairsTask = $this->makeTask($this->event, ['title' => 'Tugas ketua', 'owner_membership_id' => $this->membershipOf($chair, $this->event)->id]);
        $this->actingAs($treasurer)->postJson(route('events.tasks.update', [$this->event, $chairsTask]), [
            'title' => 'Diubah bendahara', 'priority' => 'medium', 'weight' => 2,
        ])->assertForbidden();

        $this->assertSame('Susun RAB awal dan rencana pemasukan', $mine->fresh()->title);
        $this->assertSame('Tugas ketua', $chairsTask->fresh()->title);
    }

    public function test_the_organization_may_run_the_work_of_every_event(): void
    {
        $this->actingAs($this->organizer)->postJson(route('events.tasks.insert', $this->event), [
            'title' => 'Koordinasi umum', 'priority' => 'medium', 'weight' => 1,
        ])->assertCreated();

        $this->assertNull(Task::query()->sole()->division_id);
    }

    public function test_nothing_changes_once_the_event_is_over(): void
    {
        $this->event->forceFill(['status' => 'completed'])->save();

        $this->actingAs($this->organizer)->postJson(route('events.tasks.insert', $this->event), [
            'title' => 'Terlambat', 'priority' => 'medium', 'weight' => 1,
        ])->assertForbidden();
    }

    public function test_title_priority_and_weight_are_required(): void
    {
        $this->actingAs($this->coordinator)->postJson(route('events.tasks.insert', $this->event), ['division_id' => $this->acara->id])
            ->assertStatus(422)
            ->assertJsonPath('errors.title.0', 'Kolom judul tugas wajib diisi.')
            ->assertJsonPath('errors.priority.0', 'Kolom prioritas wajib diisi.')
            ->assertJsonPath('errors.weight.0', 'Pilih besar pekerjaan: ringan, sedang, atau berat.');
    }

    public function test_the_size_of_a_task_is_light_medium_or_heavy(): void
    {
        $this->actingAs($this->coordinator)->postJson(route('events.tasks.insert', $this->event), [
            'title' => 'Terlalu besar', 'division_id' => $this->acara->id, 'priority' => 'medium', 'weight' => 7,
        ])->assertStatus(422)->assertJsonPath('errors.weight.0', 'Pilih besar pekerjaan: ringan, sedang, atau berat.');

        $this->actingAs($this->coordinator)->postJson(route('events.tasks.insert', $this->event), [
            'title' => 'Pekerjaan berat', 'division_id' => $this->acara->id, 'priority' => 'medium', 'weight' => 3,
        ])->assertCreated();

        $task = Task::query()->sole();
        $this->assertSame(3, $task->weight);
        $this->actingAs($this->coordinator)->get(route('events.tasks.detail', [$this->event, $task]))
            ->assertSee('Berat')
            ->assertDontSee('Bobot')
            ->assertDontSee('Milestone');
    }

    public function test_the_due_date_cannot_be_before_the_start(): void
    {
        $this->actingAs($this->coordinator)->postJson(route('events.tasks.insert', $this->event), [
            'title' => 'Mundur', 'division_id' => $this->acara->id, 'priority' => 'medium', 'weight' => 1,
            'starts_on' => '2026-12-10', 'due_on' => '2026-12-01',
        ])->assertStatus(422)->assertJsonPath('errors.due_on.0', 'Tenggat tidak boleh sebelum tanggal mulai.');
    }

    public function test_owner_and_collaborators_must_be_on_this_events_committee(): void
    {
        $otherEvent = $this->makeEvent(['name' => 'Event Lain']);
        $outsider = User::factory()->create();
        $this->seat($outsider, $otherEvent, CommitteeRoleKind::Secretary);
        $outsiderMembership = $this->membershipOf($outsider, $otherEvent);

        $response = $this->actingAs($this->coordinator)->postJson(route('events.tasks.insert', $this->event), [
            'title' => 'Orang luar', 'division_id' => $this->acara->id, 'priority' => 'medium', 'weight' => 1,
            'owner_membership_id' => $outsiderMembership->id,
            'assignee_ids' => [$outsiderMembership->id],
        ])->assertStatus(422);

        $this->assertSame('Penanggung jawab harus panitia aktif atau yang sedang diundang di event ini.', $response->json('errors')['owner_membership_id'][0]);
        $this->assertSame('Anggota tugas harus panitia aktif atau yang sedang diundang di event ini.', $response->json('errors')['assignee_ids.0'][0]);
        $this->assertSame(0, Task::query()->count());
    }

    public function test_a_subtask_takes_its_parents_division_and_program(): void
    {
        $program = $this->makeProgram($this->acara);
        $parent = $this->makeTask($this->event, ['division_id' => $this->acara->id, 'work_program_id' => $program->id]);

        $this->actingAs($this->coordinator)->postJson(route('events.tasks.insert', $this->event), [
            'title' => 'Bagian kecil', 'parent_id' => $parent->id, 'division_id' => null, 'priority' => 'medium', 'weight' => 1,
        ])->assertCreated();

        $subtask = Task::query()->where('title', 'Bagian kecil')->sole();
        $this->assertSame($this->acara->id, $subtask->division_id);
        $this->assertSame($program->id, $subtask->work_program_id);
    }

    public function test_subtasks_go_one_level_deep(): void
    {
        $parent = $this->makeTask($this->event, ['division_id' => $this->acara->id]);
        $subtask = $this->makeTask($this->event, ['parent_id' => $parent->id]);

        $this->actingAs($this->coordinator)->postJson(route('events.tasks.insert', $this->event), [
            'title' => 'Terlalu dalam', 'parent_id' => $subtask->id, 'priority' => 'medium', 'weight' => 1,
        ])->assertStatus(422)->assertJsonPath('errors.parent_id.0', 'Tugas induk harus tugas utama di event ini.');

        $this->actingAs($this->coordinator)->postJson(route('events.tasks.update', [$this->event, $parent]), [
            'title' => $parent->title, 'parent_id' => $this->makeTask($this->event, ['division_id' => $this->acara->id])->id, 'priority' => 'medium', 'weight' => 1,
        ])->assertStatus(422)->assertJsonPath('errors.parent_id.0', 'Tugas yang memiliki subtugas tidak dapat dijadikan subtugas.');
    }

    public function test_a_task_in_a_program_lives_in_the_programs_division(): void
    {
        $program = $this->makeProgram($this->acara);

        $this->actingAs($this->organizer)->postJson(route('events.tasks.insert', $this->event), [
            'title' => 'Ikut program', 'work_program_id' => $program->id, 'priority' => 'medium', 'weight' => 1,
        ])->assertCreated();

        $this->assertSame($this->acara->id, Task::query()->sole()->division_id);
    }

    public function test_updating_a_task_audits_what_changed_and_moves_its_subtasks_along(): void
    {
        $konsumsi = $this->makeDivision($this->event, 'Seksi Konsumsi');
        $parent = $this->makeTask($this->event, ['division_id' => $this->acara->id]);
        $subtask = $this->makeTask($this->event, ['parent_id' => $parent->id]);

        $this->actingAs($this->organizer)->postJson(route('events.tasks.update', [$this->event, $parent]), [
            'title' => 'Judul baru', 'division_id' => $konsumsi->id, 'priority' => 'critical', 'weight' => 2,
        ])->assertOk();

        $this->assertSame($konsumsi->id, $subtask->fresh()->division_id);
        $entry = AuditLog::query()->where('action', 'tasks.updated')->sole();
        $this->assertSame('Pesan sound system', $entry->before['title']);
        $this->assertSame('Judul baru', $entry->after['title']);
    }

    public function test_status_cannot_be_set_through_the_edit_form(): void
    {
        $task = $this->makeTask($this->event, ['division_id' => $this->acara->id]);

        $this->actingAs($this->coordinator)->postJson(route('events.tasks.update', [$this->event, $task]), [
            'title' => $task->title, 'division_id' => $this->acara->id, 'priority' => 'medium', 'weight' => 1, 'status' => 'completed', 'completed_at' => now(),
        ])->assertOk();

        $this->assertSame(TaskStatus::Todo, $task->fresh()->status);
        $this->assertNull($task->fresh()->completed_at);
    }
}
