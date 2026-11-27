<?php

namespace Tests\Feature\Work;

use App\Enums\CommitteeRoleKind;
use App\Enums\WorkProgramStatus;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Event;
use App\Models\User;
use App\Models\WorkProgram;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

class WorkProgramTest extends TestCase
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function payload(array $overrides = []): array
    {
        return [
            'division_id' => $this->acara->id,
            'title' => 'Persiapan liturgi',
            'objective' => 'Ibadah berjalan khidmat',
            'status' => 'active',
            'starts_on' => '2026-11-01',
            'ends_on' => '2026-12-20',
            ...$overrides,
        ];
    }

    public function test_a_coordinator_creates_a_program_for_their_division_and_it_is_audited(): void
    {
        $this->actingAs($this->coordinator)->postJson(route('events.programs.insert', $this->event), $this->payload([
            'pic_membership_id' => $this->membershipOf($this->coordinator, $this->event)->id,
        ]))->assertCreated();

        $program = WorkProgram::query()->sole();
        $this->assertSame(WorkProgramStatus::Active, $program->status);
        $this->assertSame('Ibadah berjalan khidmat', $program->objective);
        $this->assertSame($this->event->id, AuditLog::query()->where('action', 'work-programs.created')->sole()->event_id);
    }

    public function test_a_coordinator_cannot_plan_for_another_division_or_move_a_program_there(): void
    {
        $konsumsi = $this->makeDivision($this->event, 'Seksi Konsumsi');
        $program = $this->makeProgram($this->acara);

        $this->actingAs($this->coordinator)->postJson(route('events.programs.insert', $this->event), $this->payload(['division_id' => $konsumsi->id]))->assertForbidden();
        $this->actingAs($this->coordinator)->postJson(route('events.programs.update', [$this->event, $program]), $this->payload(['division_id' => $konsumsi->id]))->assertForbidden();

        $this->assertSame($this->acara->id, $program->fresh()->division_id);
    }

    public function test_the_core_team_plans_its_own_programs_but_not_those_of_a_division(): void
    {
        $chair = User::factory()->create();
        $this->seat($chair, $this->event, CommitteeRoleKind::Chair);

        $this->actingAs($chair)->postJson(route('events.programs.insert', $this->event), $this->payload(['division_id' => null, 'title' => 'Koordinasi pengurus inti']))->assertCreated();
        $this->actingAs($chair)->postJson(route('events.programs.insert', $this->event), $this->payload())->assertForbidden();

        $this->assertNull(WorkProgram::query()->sole()->division_id);
    }

    public function test_a_division_coordinator_cannot_plan_for_the_core_team(): void
    {
        $this->actingAs($this->coordinator)->postJson(route('events.programs.insert', $this->event), $this->payload(['division_id' => null]))->assertForbidden();
    }

    public function test_core_team_programs_come_first_and_are_labelled(): void
    {
        $this->makeProgram($this->acara, ['title' => 'Program seksi']);
        $this->makeProgram($this->acara, ['title' => 'Program inti', 'division_id' => null]);

        $programs = $this->actingAs($this->organizer)->getJson(route('events.work.data', $this->event))->json('data.programs');

        $this->assertSame(['Program inti', 'Program seksi'], array_column($programs, 'title'));
        $this->assertSame('Pengurus Inti', $programs[0]['division']);
    }

    public function test_a_program_needs_a_title_and_a_valid_period(): void
    {
        $this->actingAs($this->organizer)->postJson(route('events.programs.insert', $this->event), $this->payload([
            'title' => '', 'ends_on' => '2026-10-01',
        ]))->assertStatus(422)
            ->assertJsonPath('errors.title.0', 'Kolom nama program wajib diisi.')
            ->assertJsonPath('errors.ends_on.0', 'Tanggal selesai tidak boleh sebelum tanggal mulai.');
    }

    public function test_a_program_without_tasks_can_be_deleted(): void
    {
        $program = $this->makeProgram($this->acara);

        $this->actingAs($this->coordinator)->postJson(route('events.programs.delete', [$this->event, $program]))->assertOk();

        $this->assertModelMissing($program);
        $this->assertSame(1, AuditLog::query()->where('action', 'work-programs.deleted')->count());
    }

    public function test_a_program_with_tasks_is_kept(): void
    {
        $program = $this->makeProgram($this->acara);
        $this->makeTask($this->event, ['work_program_id' => $program->id]);

        $this->actingAs($this->coordinator)->postJson(route('events.programs.delete', [$this->event, $program]))
            ->assertStatus(422)
            ->assertJsonPath('data', 'Program kerja ini sudah memiliki tugas. Ubah statusnya menjadi Dibatalkan bila tidak dilanjutkan.');

        $this->assertModelExists($program);
    }
}
