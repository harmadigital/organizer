<?php

namespace Tests\Feature\Notifications;

use App\Actions\Meetings\SaveMeeting;
use App\Actions\Work\CompleteTask;
use App\Enums\CommitteeRoleKind;
use App\Models\Division;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

/**
 * Reminders for tomorrow are written when the person opens the app, so no scheduler is
 * needed, and never twice for the same task or meeting day.
 */
class RemindersTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    private Division $acara;

    private User $owner;

    private User $collaborator;

    private User $konsumsiMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-12-01 05:00:00'); // Selasa 12.00 WIB
        $this->setUpCommittees();
        $this->event = $this->makeEvent();
        $this->acara = $this->makeDivision($this->event, 'Seksi Acara');
        $this->owner = User::factory()->create();
        $this->seat($this->owner, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);
        $this->collaborator = User::factory()->create();
        $this->seat($this->collaborator, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);
        $this->konsumsiMember = User::factory()->create();
        $this->seat($this->konsumsiMember, $this->event, CommitteeRoleKind::DivisionMember, $this->makeDivision($this->event, 'Seksi Konsumsi'));
    }

    private function openApp(User $user): void
    {
        $this->actingAs($user)->get(route('notifications.index'))->assertOk();
    }

    public function test_the_owner_and_collaborators_are_reminded_the_day_before_once(): void
    {
        $this->makeTask($this->event, [
            'title' => 'Cetak liturgi',
            'division_id' => $this->acara->id,
            'due_on' => '2026-12-02',
            'owner_membership_id' => $this->membershipOf($this->owner, $this->event)->id,
        ], [$this->membershipOf($this->collaborator, $this->event)]);

        $this->openApp($this->owner);
        $this->travel(2)->hours();
        $this->openApp($this->owner);
        $this->openApp($this->collaborator);
        $this->openApp($this->konsumsiMember);

        $this->assertSame('Tenggat besok: Cetak liturgi', $this->owner->notifications()->where('type', 'task.due')->sole()->data['title']);
        $this->assertCount(1, $this->collaborator->notifications()->where('type', 'task.due')->get());
        $this->assertCount(0, $this->konsumsiMember->notifications()->where('type', 'task.due')->get());
    }

    public function test_finished_tasks_and_later_deadlines_get_no_reminder(): void
    {
        $owner = $this->membershipOf($this->owner, $this->event)->id;
        $finished = $this->makeTask($this->event, ['division_id' => $this->acara->id, 'due_on' => '2026-12-02', 'owner_membership_id' => $owner]);
        $this->app->make(CompleteTask::class)->handle($finished, 'Beres.', [], $this->organizer);
        $this->makeTask($this->event, ['division_id' => $this->acara->id, 'due_on' => '2026-12-03', 'owner_membership_id' => $owner]);

        $this->openApp($this->owner);

        $this->assertCount(0, $this->owner->notifications()->where('type', 'task.due')->get());
    }

    public function test_a_division_meeting_tomorrow_reminds_only_that_division(): void
    {
        $this->app->make(SaveMeeting::class)->handle($this->event, new Meeting, [
            'title' => 'Rapat seksi acara', 'meets_on' => '2026-12-02', 'starts_at' => '19:00', 'division_id' => $this->acara->id,
        ], $this->organizer);

        $this->openApp($this->owner);
        $this->openApp($this->konsumsiMember);

        $this->assertSame('Besok rapat: Rapat seksi acara', $this->owner->notifications()->where('type', 'meeting.tomorrow')->sole()->data['title']);
        $this->assertCount(0, $this->konsumsiMember->notifications()->where('type', 'meeting.tomorrow')->get());
    }
}
