<?php

namespace Tests\Feature\Notifications;

use App\Actions\Meetings\DeleteMeeting;
use App\Actions\Meetings\SaveMeeting;
use App\Actions\Meetings\SaveMinutes;
use App\Enums\CommitteeRoleKind;
use App\Models\Division;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

/**
 * Notices about meetings go to the people the meeting is about: its division and the
 * divisions below it, or the whole committee for a core team meeting.
 */
class MeetingNoticesTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    private Division $acara;

    private User $secretary;

    private User $acaraMember;

    private User $liturgiMember;

    private User $konsumsiMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-12-01 05:00:00');
        $this->setUpCommittees();
        $this->event = $this->makeEvent();
        $this->acara = $this->makeDivision($this->event, 'Seksi Acara');
        $liturgi = $this->makeDivision($this->event, 'Tim Liturgi', $this->acara);
        $konsumsi = $this->makeDivision($this->event, 'Seksi Konsumsi');
        $this->secretary = User::factory()->create(['name' => 'Maria']);
        $this->seat($this->secretary, $this->event, CommitteeRoleKind::Secretary);
        $this->acaraMember = User::factory()->create();
        $this->seat($this->acaraMember, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);
        $this->liturgiMember = User::factory()->create();
        $this->seat($this->liturgiMember, $this->event, CommitteeRoleKind::DivisionMember, $liturgi);
        $this->konsumsiMember = User::factory()->create();
        $this->seat($this->konsumsiMember, $this->event, CommitteeRoleKind::DivisionMember, $konsumsi);
    }

    private function schedule(array $attributes = [], ?Meeting $meeting = null): Meeting
    {
        return $this->app->make(SaveMeeting::class)->handle($this->event, $meeting ?? new Meeting, [
            'title' => 'Rapat koordinasi',
            'meets_on' => '2026-12-05',
            'starts_at' => '19:00',
            'location' => 'Aula',
            ...$attributes,
        ], $this->secretary);
    }

    /**
     * @return list<int>
     */
    private function toldAbout(string $type): array
    {
        return DatabaseNotification::query()->where('type', $type)->orderBy('notifiable_id')->pluck('notifiable_id')->map(fn ($id) => (int) $id)->all();
    }

    public function test_a_division_meeting_is_announced_to_that_division_and_the_divisions_below_it(): void
    {
        $this->schedule(['division_id' => $this->acara->id]);

        $this->assertSame([$this->acaraMember->id, $this->liturgiMember->id], $this->toldAbout('meeting.scheduled'));
        $this->assertSame('Sabtu, 5 Desember 2026 · 19.00 · Aula', $this->acaraMember->notifications->sole()->data['line']);
    }

    public function test_a_core_team_meeting_is_announced_to_everyone_except_who_scheduled_it(): void
    {
        $this->schedule();

        $this->assertSame([$this->acaraMember->id, $this->liturgiMember->id, $this->konsumsiMember->id], $this->toldAbout('meeting.scheduled'));
    }

    public function test_moving_the_time_tells_people_once_but_editing_the_agenda_does_not(): void
    {
        $meeting = $this->schedule();

        $this->schedule(['agenda' => 'Pembagian tugas'], $meeting);
        $this->assertSame([], $this->toldAbout('meeting.changed'));

        $this->schedule(['starts_at' => '18:30'], $meeting);
        $this->schedule(['starts_at' => '18:30', 'agenda' => 'Pembagian tugas dan konsumsi'], $meeting);

        $this->assertCount(3, $this->toldAbout('meeting.changed'));
        $this->assertSame('Jadwal rapat berubah: Rapat koordinasi', $this->konsumsiMember->notifications()->where('type', 'meeting.changed')->sole()->data['title']);
    }

    public function test_a_meeting_entered_after_it_took_place_tells_nobody(): void
    {
        $this->schedule(['meets_on' => '2026-11-20']);

        $this->assertSame(0, DatabaseNotification::query()->count());
    }

    public function test_deleting_an_upcoming_meeting_says_it_is_cancelled_and_clears_its_earlier_notices(): void
    {
        $meeting = $this->schedule();

        $this->app->make(DeleteMeeting::class)->handle($meeting, $this->secretary);

        $this->assertSame([], $this->toldAbout('meeting.scheduled'));
        $notice = $this->acaraMember->notifications()->where('type', 'meeting.cancelled')->sole();
        $this->assertSame('Rapat dibatalkan: Rapat koordinasi', $notice->data['title']);
        $this->assertSame(route('events.meetings', $this->event, absolute: false), $notice->data['path']);
    }

    public function test_only_the_first_save_of_the_minutes_tells_people(): void
    {
        $meeting = $this->schedule();
        $saveMinutes = $this->app->make(SaveMinutes::class);

        $saveMinutes->handle($meeting, '<p>Hasil rapat</p>', [], [], 0, $this->secretary);
        $saveMinutes->handle($meeting->fresh(), '<p>Hasil rapat, diperbaiki</p>', [], [], 1, $this->secretary);

        $this->assertCount(3, $this->toldAbout('minutes.published'));
        $this->assertStringEndsWith('#notulen', $this->acaraMember->notifications()->where('type', 'minutes.published')->sole()->data['path']);
    }

    public function test_opening_the_meeting_marks_its_notices_read(): void
    {
        $meeting = $this->schedule();

        $this->actingAs($this->acaraMember)->get(route('events.meetings.detail', [$this->event, $meeting]))->assertOk();

        $this->assertNotNull($this->acaraMember->notifications->sole()->read_at);
        $this->assertNull($this->konsumsiMember->notifications->sole()->read_at);
    }
}
