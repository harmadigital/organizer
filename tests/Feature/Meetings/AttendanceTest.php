<?php

namespace Tests\Feature\Meetings;

use App\Actions\Meetings\SaveMeeting;
use App\Enums\CommitteeRoleKind;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

/**
 * Attendance: everyone starts as absent; whoever runs the meeting moves people to present.
 */
class AttendanceTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    private User $secretary;

    private User $member;

    private Meeting $meeting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-12-01 12:30:00'); // 19:30 WIB, during the meeting
        $this->setUpCommittees();
        $this->event = $this->makeEvent();
        $this->secretary = User::factory()->create(['name' => 'Sekretaris']);
        $this->seat($this->secretary, $this->event, CommitteeRoleKind::Secretary);
        $this->member = User::factory()->create(['name' => 'Anggota Acara']);
        $this->seat($this->member, $this->event, CommitteeRoleKind::DivisionMember, $this->makeDivision($this->event));
        $this->meeting = $this->app->make(SaveMeeting::class)->handle($this->event, new Meeting, [
            'title' => 'Rapat pleno', 'meets_on' => '2026-12-01', 'starts_at' => '19:00', 'ends_at' => '21:00',
        ], $this->organizer);
    }

    private function mark(User $actor, User $person, bool $present)
    {
        return $this->actingAs($actor)->postJson(route('events.meetings.attendance', [$this->event, $this->meeting]), [
            'membership_id' => $this->membershipOf($person, $this->event)->id,
            'present' => $present,
        ]);
    }

    public function test_everyone_is_absent_until_marked_present(): void
    {
        $view = $this->actingAs($this->secretary)->get(route('events.meetings.detail', [$this->event, $this->meeting]))->assertOk();

        $this->assertSame([], $view->viewData('presentIds'));
        $this->assertSame(['Anggota Acara', 'Sekretaris'], $view->viewData('committee')->map(fn ($membership) => $membership->user->name)->all());
    }

    public function test_marking_someone_present_and_back_is_saved_at_once(): void
    {
        $this->mark($this->secretary, $this->member, true)->assertOk()->assertJsonPath('data.present_count', 1);
        $this->mark($this->secretary, $this->member, true)->assertOk()->assertJsonPath('data.present_count', 1);
        $this->assertSame(['Anggota Acara'], $this->meeting->attendees()->with('user')->get()->map(fn ($attendee) => $attendee->user->name)->all());

        $this->mark($this->secretary, $this->member, false)->assertOk()->assertJsonPath('data.present_count', 0);
        $this->assertSame(0, $this->meeting->attendees()->count());
    }

    public function test_only_people_who_run_the_meeting_mark_attendance(): void
    {
        $this->mark($this->member, $this->member, true)->assertForbidden();

        $this->actingAs($this->member)->get(route('events.meetings.detail', [$this->event, $this->meeting]))
            ->assertOk()
            ->assertDontSee('data-attendance-editable', false);
    }

    public function test_only_committee_members_of_this_event_can_be_marked(): void
    {
        $otherEvent = $this->makeEvent(['name' => 'Event lain']);
        $outsider = User::factory()->create();
        $this->seat($outsider, $otherEvent, CommitteeRoleKind::Secretary);

        $this->actingAs($this->secretary)->postJson(route('events.meetings.attendance', [$this->event, $this->meeting]), [
            'membership_id' => $this->membershipOf($outsider, $otherEvent)->id,
            'present' => true,
        ])->assertStatus(422)->assertJsonPath('errors.membership_id.0', 'Orang ini bukan panitia event ini.');
    }

    public function test_attendance_opens_on_the_meeting_day_and_locks_when_the_meeting_is_over(): void
    {
        $this->travelTo('2026-11-30 10:00:00'); // 17:00 WIB the day before
        $this->mark($this->secretary, $this->member, true)
            ->assertStatus(422)
            ->assertJsonPath('errors.attendance.0', 'Absensi baru dapat diisi pada hari rapat.');

        $this->travelTo('2026-12-01 08:00:00'); // 15:00 WIB on the day
        $this->mark($this->secretary, $this->member, true)->assertOk();

        // 21:00 Jakarta time is 14:00 UTC.
        $this->travelTo('2026-12-01 14:00:00');
        $this->mark($this->secretary, $this->member, false)
            ->assertStatus(422)
            ->assertJsonPath('errors.attendance.0', 'Absensi rapat ini sudah dikunci karena rapat sudah selesai.');

        $this->assertSame(1, $this->meeting->attendees()->count());
        $this->actingAs($this->secretary)->get(route('events.meetings.detail', [$this->event, $this->meeting]))
            ->assertSee('Absensi dikunci')
            ->assertDontSee('data-attendance-editable', false);
    }

    public function test_without_an_end_time_attendance_locks_at_the_end_of_the_meeting_day(): void
    {
        $this->meeting->forceFill(['ends_at' => null])->save();

        // 23:30 Jakarta time is 16:30 UTC.
        $this->travelTo('2026-12-01 16:30:00');
        $this->mark($this->secretary, $this->member, true)->assertOk();

        // 00:00 the next day in Jakarta is 17:00 UTC.
        $this->travelTo('2026-12-01 17:00:00');
        $this->mark($this->secretary, $this->member, false)->assertStatus(422);
    }
}
