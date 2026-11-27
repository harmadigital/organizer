<?php

namespace Tests\Feature\Meetings;

use App\Actions\Meetings\SaveMeeting;
use App\Enums\CommitteeRoleKind;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\MeetingComment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\TestCase;

/**
 * Meetings are visible to the whole committee without invitations; only the secretary
 * (core team) and division coordinators (their division) schedule them.
 */
class MeetingTest extends TestCase
{
    use BuildsCommittees;
    use LazilyRefreshDatabase;

    private Event $event;

    private Division $acara;

    private User $secretary;

    private User $coordinator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-27 10:00:00');
        $this->setUpCommittees();
        $this->event = $this->makeEvent();
        $this->acara = $this->makeDivision($this->event, 'Seksi Acara');
        $this->secretary = User::factory()->create(['name' => 'Sekretaris Panitia']);
        $this->seat($this->secretary, $this->event, CommitteeRoleKind::Secretary);
        $this->coordinator = User::factory()->create();
        $this->seat($this->coordinator, $this->event, CommitteeRoleKind::DivisionCoordinator, $this->acara);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'Rapat pleno 1',
            'meets_on' => '2026-12-01',
            'starts_at' => '19:00',
            'ends_at' => '21:00',
            'location' => 'Ruang konsistori',
            'agenda' => "Laporan seksi\nSusunan acara",
            ...$overrides,
        ];
    }

    private function makeMeeting(array $attributes = []): Meeting
    {
        return $this->app->make(SaveMeeting::class)->handle($this->event, new Meeting, [...$this->payload(), ...$attributes], $this->organizer);
    }

    public function test_the_secretary_schedules_a_core_team_meeting_and_it_is_audited(): void
    {
        $this->actingAs($this->secretary)->postJson(route('events.meetings.insert', $this->event), $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.url', route('events.meetings.detail', [$this->event, Meeting::query()->sole()]));

        $meeting = Meeting::query()->sole();
        $this->assertNull($meeting->division_id);
        $this->assertSame(['Laporan seksi', 'Susunan acara'], $meeting->agendaItems());
        $this->assertSame('19.00–21.00', $meeting->timeLabel());
        $this->assertSame($this->event->id, AuditLog::query()->where('action', 'meetings.created')->sole()->event_id);
    }

    public function test_a_coordinator_schedules_meetings_for_their_division_only(): void
    {
        $this->actingAs($this->coordinator)->postJson(route('events.meetings.insert', $this->event), $this->payload(['division_id' => $this->acara->id]))->assertCreated();
        $this->actingAs($this->coordinator)->postJson(route('events.meetings.insert', $this->event), $this->payload(['title' => 'Rapat inti']))->assertForbidden();
        $this->actingAs($this->coordinator)->postJson(route('events.meetings.insert', $this->event), $this->payload(['division_id' => $this->makeDivision($this->event, 'Seksi Konsumsi')->id]))->assertForbidden();

        $this->assertSame(1, Meeting::query()->count());
    }

    public function test_a_division_member_cannot_schedule_meetings(): void
    {
        $member = User::factory()->create();
        $this->seat($member, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);

        $this->actingAs($member)->postJson(route('events.meetings.insert', $this->event), $this->payload(['division_id' => $this->acara->id]))->assertForbidden();
    }

    public function test_a_meeting_needs_a_name_a_date_and_a_start_time_that_comes_before_the_end(): void
    {
        $this->actingAs($this->secretary)->postJson(route('events.meetings.insert', $this->event), ['ends_at' => '18:00', 'starts_at' => '19:00'])
            ->assertStatus(422)
            ->assertJsonPath('errors.title.0', 'Kolom nama rapat wajib diisi.')
            ->assertJsonPath('errors.meets_on.0', 'Kolom tanggal wajib diisi.')
            ->assertJsonPath('errors.ends_at.0', 'Jam selesai harus setelah jam mulai.');

        $this->actingAs($this->secretary)->postJson(route('events.meetings.insert', $this->event), $this->payload(['starts_at' => '7 malam']))
            ->assertStatus(422)
            ->assertJsonPath('errors.starts_at.0', 'Pilih jam mulai, misalnya 19:00.');
    }

    public function test_every_committee_member_sees_every_meeting_without_an_invitation(): void
    {
        $this->makeMeeting(['title' => 'Rapat inti']);
        $this->makeMeeting(['title' => 'Rapat seksi acara', 'division_id' => $this->acara->id]);
        $memberOfAnotherDivision = User::factory()->create();
        $this->seat($memberOfAnotherDivision, $this->event, CommitteeRoleKind::DivisionMember, $this->makeDivision($this->event, 'Seksi Konsumsi'));

        $this->actingAs($memberOfAnotherDivision)->get(route('events.meetings', $this->event))
            ->assertOk()
            ->assertSee('Rapat inti')
            ->assertSee('Rapat seksi acara')
            ->assertDontSee('data-meeting-add', false);

        $this->actingAs(User::factory()->create())->get(route('events.meetings', $this->event))->assertNotFound();
    }

    public function test_upcoming_and_past_meetings_are_listed_apart(): void
    {
        $this->makeMeeting(['title' => 'Rapat lalu', 'meets_on' => '2026-11-20']);
        $this->makeMeeting(['title' => 'Rapat nanti', 'meets_on' => '2026-12-05']);

        $view = $this->actingAs($this->secretary)->get(route('events.meetings', $this->event))->assertOk();

        $this->assertSame(['Rapat nanti'], $view->viewData('upcoming')->pluck('title')->all());
        $this->assertSame(['Rapat lalu'], $view->viewData('past')->pluck('title')->all());
    }

    public function test_a_meeting_without_minutes_can_be_deleted_but_one_with_minutes_stays(): void
    {
        $empty = $this->makeMeeting();
        $this->actingAs($this->secretary)->postJson(route('events.meetings.delete', [$this->event, $empty]))->assertOk();
        $this->assertModelMissing($empty);

        $withMinutes = $this->makeMeeting();
        $withMinutes->forceFill(['minutes' => 'Isi', 'minutes_version' => 1])->save();
        $this->actingAs($this->secretary)->postJson(route('events.meetings.delete', [$this->event, $withMinutes]))
            ->assertStatus(422)
            ->assertJsonPath('data', 'Rapat yang sudah memiliki notulen tidak dapat dihapus.');
        $this->assertModelExists($withMinutes);
    }

    public function test_every_member_comments_and_only_the_author_or_a_manager_removes_it(): void
    {
        $meeting = $this->makeMeeting();
        $member = User::factory()->create();
        $this->seat($member, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);

        $this->actingAs($member)->postJson(route('events.meetings.comments.insert', [$this->event, $meeting]), ['body' => 'Saya izin terlambat.'])->assertCreated();
        $comment = MeetingComment::query()->sole();

        $this->actingAs($this->coordinator)->postJson(route('events.meetings.comments.delete', [$this->event, $meeting, $comment]))->assertForbidden();
        $this->actingAs($this->secretary)->postJson(route('events.meetings.comments.delete', [$this->event, $meeting, $comment]))->assertOk();

        $this->assertModelMissing($comment);
    }

    public function test_a_comment_of_another_meeting_cannot_be_reached_through_this_one(): void
    {
        $meeting = $this->makeMeeting();
        $other = $this->makeMeeting(['title' => 'Rapat lain']);
        $this->actingAs($this->secretary)->postJson(route('events.meetings.comments.insert', [$this->event, $other]), ['body' => 'Di rapat lain'])->assertCreated();

        $this->actingAs($this->secretary)->postJson(route('events.meetings.comments.delete', [$this->event, $meeting, MeetingComment::query()->sole()]))->assertNotFound();
    }

    public function test_meeting_text_is_escaped_on_its_page(): void
    {
        $meeting = $this->makeMeeting(['title' => '<script>alert(1)</script>', 'agenda' => '<b>tebal</b>']);

        $html = $this->actingAs($this->secretary)->get(route('events.meetings.detail', [$this->event, $meeting]))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<b>tebal</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;tebal&lt;/b&gt;', $html);
    }

    public function test_the_overview_shows_the_next_meeting(): void
    {
        $this->makeMeeting(['title' => 'Rapat lalu', 'meets_on' => '2026-11-20']);
        $this->makeMeeting(['title' => 'Rapat terdekat', 'meets_on' => '2026-11-30']);
        $this->makeMeeting(['title' => 'Rapat nanti', 'meets_on' => '2026-12-10']);

        $this->actingAs($this->coordinator)->get(route('events.overview', $this->event))
            ->assertOk()
            ->assertViewHas('nextMeeting', fn (?Meeting $meeting) => $meeting?->title === 'Rapat terdekat');
    }
}
