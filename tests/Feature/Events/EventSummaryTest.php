<?php

namespace Tests\Feature\Events;

use App\Actions\Meetings\SaveMeeting;
use App\Actions\Work\CompleteTask;
use App\Enums\CommitteeRoleKind;
use App\Enums\TaskStatus;
use App\Models\Division;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\MeetingActionItem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

/**
 * "Laporan Kondisi": the whole event at a glance for the core team and the supervisors.
 */
class EventSummaryTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    private Division $acara;

    private Division $liturgi;

    private Division $konsumsi;

    private User $chair;

    private User $coordinator;

    protected function setUp(): void
    {
        parent::setUp();

        // 1 December 2026, 12:00 WIB. Preparation runs from 1 October to 24 December:
        // 61 of 84 days have passed, so 72% of the time is used.
        $this->travelTo('2026-12-01 05:00:00');
        $this->setUpCommittees();
        $this->event = $this->makeEvent(['committee_starts_on' => '2026-10-01']);
        $this->acara = $this->makeDivision($this->event, 'Seksi Acara');
        $this->liturgi = $this->makeDivision($this->event, 'Tim Liturgi', $this->acara);
        $this->konsumsi = $this->makeDivision($this->event, 'Seksi Konsumsi');
        $this->chair = User::factory()->create(['name' => 'Ketua']);
        $this->seat($this->chair, $this->event, CommitteeRoleKind::Chair);
        $this->coordinator = User::factory()->create(['name' => 'Koordinator Acara']);
        $this->seat($this->coordinator, $this->event, CommitteeRoleKind::DivisionCoordinator, $this->acara);
    }

    private function summary(User $viewer)
    {
        return $this->actingAs($viewer)->getJson(route('events.summary', $this->event));
    }

    private function finish(Task $task): void
    {
        $this->app->make(CompleteTask::class)->handle($task, 'Beres.', [], $this->organizer);
    }

    public function test_the_core_team_supervisors_and_the_organization_read_the_report(): void
    {
        $supervisor = User::factory()->create();
        $this->seat($supervisor, $this->event, CommitteeRoleKind::Supervisor);
        $treasurer = User::factory()->create();
        $this->seat($treasurer, $this->event, CommitteeRoleKind::Treasurer);

        foreach ([$this->chair, $supervisor, $treasurer, $this->organizer] as $reader) {
            $this->summary($reader)->assertOk()->assertJsonPath('data.event.name', 'Perayaan Natal 2026');
            $this->actingAs($reader)->get(route('events.overview', $this->event))->assertOk()->assertSee('data-summary-open', false);
        }
    }

    public function test_division_coordinators_and_members_do_not_get_the_report(): void
    {
        $member = User::factory()->create();
        $this->seat($member, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);

        foreach ([$this->coordinator, $member] as $person) {
            $this->summary($person)->assertForbidden();
            $this->actingAs($person)->get(route('events.overview', $this->event))->assertOk()->assertDontSee('data-summary-open', false);
        }
    }

    public function test_a_core_role_counts_only_once_the_invitation_is_accepted(): void
    {
        $invited = User::factory()->create();
        $this->seat($invited, $this->event, CommitteeRoleKind::Secretary, accepted: false);

        $this->summary($invited)->assertNotFound();
    }

    public function test_work_is_summed_up_per_top_level_division_against_the_preparation_time(): void
    {
        $this->finish($this->makeTask($this->event, ['title' => 'Susun liturgi', 'division_id' => $this->acara->id]));
        $this->makeTask($this->event, ['title' => 'Pesan sound system', 'division_id' => $this->acara->id, 'due_on' => '2026-11-20']);
        $this->makeTask($this->event, ['title' => 'Cetak liturgi', 'division_id' => $this->liturgi->id, 'due_on' => '2026-12-05']);
        $this->makeTask($this->event, ['title' => 'Susun RAB', 'due_on' => '2026-12-10']);
        $cancelled = $this->makeTask($this->event, ['title' => 'Sewa tenda', 'division_id' => $this->konsumsi->id, 'due_on' => '2026-11-01']);
        $cancelled->forceFill(['status' => TaskStatus::Cancelled])->save();

        $report = $this->summary($this->chair)->assertOk()->json('data');

        $this->assertSame(72, $report['elapsed_percent']);
        $this->assertSame(['percent' => 25, 'total' => 4, 'done' => 1, 'open' => 3, 'overdue' => 1, 'due_soon' => 1], array_diff_key($report['work'], ['health' => true]));
        $this->assertSame('Tertinggal', $report['work']['health']['label']);
        $this->assertSame('Waktu persiapan sudah berjalan 72%, pekerjaan selesai 25%. 1 tugas terlambat.', $report['work']['health']['reason']);
        $this->assertSame('23 hari lagi', $report['event']['countdown']);

        $divisions = collect($report['divisions'])->keyBy('name');
        $this->assertSame(['Pengurus Inti', 'Seksi Acara', 'Seksi Konsumsi'], $divisions->keys()->all());
        $this->assertSame([3, 1, 1, 33], [$divisions['Seksi Acara']['total'], $divisions['Seksi Acara']['done'], $divisions['Seksi Acara']['overdue'], $divisions['Seksi Acara']['percent']]);
        $this->assertSame(['Belum ada tugas', 0], [$divisions['Seksi Konsumsi']['health']['label'], $divisions['Seksi Konsumsi']['total']]);

        $this->assertSame([['Pesan sound system', 'Seksi Acara', 'terlambat 11 hari']], collect($report['attention']['overdue']['items'])->map(fn (array $item) => [$item['title'], $item['division'], $item['when']])->all());
        $this->assertSame([['Cetak liturgi', 'Seksi Acara', (string) $this->acara->id], ['Susun RAB', 'Pengurus Inti', 'none']], collect($report['attention']['upcoming']['items'])->map(fn (array $item) => [$item['title'], $item['division'], $item['division_key']])->all());

        $this->assertSame(['Seksi Konsumsi'], $report['committee']['without_coordinator']);
        $this->assertSame(['Seksi Konsumsi'], $report['committee']['without_tasks']);
    }

    public function test_the_verdict_follows_finished_work_against_the_time_used(): void
    {
        $open = $this->makeTask($this->event, ['title' => 'Susun RAB', 'due_on' => '2026-12-10']);
        $this->finish($this->makeTask($this->event, ['title' => 'Bentuk panitia']));

        $this->assertSame('Perlu perhatian', $this->summary($this->chair)->json('data.work.health.label'));

        $this->finish($open);

        $this->assertSame('Sesuai jadwal', $this->summary($this->chair)->json('data.work.health.label'));
    }

    public function test_programs_meetings_and_follow_ups_that_need_attention_are_flagged(): void
    {
        $onTrack = $this->makeProgram($this->acara, ['title' => 'Liturgi', 'starts_on' => '2026-10-01', 'ends_on' => '2026-12-20']);
        $this->makeTask($this->event, ['division_id' => $this->acara->id, 'work_program_id' => $onTrack->id, 'due_on' => '2026-12-15']);
        $late = $this->makeProgram($this->acara, ['title' => 'Dekorasi', 'starts_on' => '2026-10-01', 'ends_on' => '2026-11-30']);
        $this->makeTask($this->event, ['division_id' => $this->acara->id, 'work_program_id' => $late->id, 'due_on' => '2026-12-15']);

        $saveMeeting = $this->app->make(SaveMeeting::class);
        $held = $saveMeeting->handle($this->event, new Meeting, ['title' => 'Rapat pleno 1', 'meets_on' => '2026-11-20', 'starts_at' => '19:00'], $this->organizer);
        $held->attendees()->attach($this->membershipOf($this->chair, $this->event)->id, ['event_id' => $this->event->id]);
        $saveMeeting->handle($this->event, new Meeting, ['title' => 'Rapat pleno 2', 'meets_on' => '2026-12-05', 'starts_at' => '19:00'], $this->organizer);
        $followUp = $this->makeTask($this->event, ['title' => 'Kirim undangan', 'due_on' => '2026-11-25']);
        MeetingActionItem::query()->create(['event_id' => $this->event->id, 'meeting_id' => $held->id, 'title' => 'Kirim undangan', 'due_on' => '2026-11-25', 'task_id' => $followUp->id]);

        $report = $this->summary($this->chair)->assertOk()->json('data');

        $this->assertSame([['Dekorasi', true, true], ['Liturgi', false, false]], collect($report['programs'])->map(fn (array $program) => [$program['title'], $program['is_past_period'], $program['needs_attention']])->all());
        $this->assertSame('Rapat pleno 2', $report['meetings']['next']['title']);
        $this->assertSame(1, $report['meetings']['held']);
        $this->assertSame(['Rapat pleno 1'], collect($report['meetings']['without_minutes'])->pluck('title')->all());
        $this->assertSame([['Rapat pleno 1', 1]], collect($report['meetings']['attendance'])->map(fn (array $meeting) => [$meeting['title'], $meeting['present']])->all());
        $this->assertSame([1, 1, 1], [$report['meetings']['follow_ups']['total'], $report['meetings']['follow_ups']['open'], $report['meetings']['follow_ups']['overdue']]);

        $this->finish($followUp);

        $this->assertSame(0, $this->summary($this->chair)->json('data.meetings.follow_ups.open'));
    }
}
