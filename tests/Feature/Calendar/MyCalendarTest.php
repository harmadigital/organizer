<?php

namespace Tests\Feature\Calendar;

use App\Actions\Calendar\SaveCalendarEntry;
use App\Actions\Events\EndAssignment;
use App\Actions\Meetings\SaveMeeting;
use App\Enums\CommitteeRoleKind;
use App\Enums\EventStatus;
use App\Enums\MembershipStatus;
use App\Models\CalendarEntry;
use App\Models\Event;
use App\Models\EventMembership;
use App\Models\Meeting;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

/**
 * Kalender Saya: one person's schedule across the events they are an active member of.
 */
class MyCalendarTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    private User $person;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-27 10:00:00');
        $this->setUpCommittees();
        $this->event = $this->makeEvent();
        $this->person = User::factory()->create(['name' => 'Anggota Acara']);
        $this->seat($this->person, $this->event, CommitteeRoleKind::DivisionMember, $this->makeDivision($this->event, 'Seksi Acara'));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function feed(User $viewer, array $query = []): TestResponse
    {
        return $this->actingAs($viewer)->getJson(route('calendar.feed', ['start' => '2026-11-30', 'end' => '2027-01-11', ...$query]));
    }

    /**
     * @param  array<string, mixed>  $query
     * @return Collection<string, array<string, mixed>>
     */
    private function items(User $viewer, array $query = []): Collection
    {
        return collect($this->feed($viewer, $query)->assertOk()->json('data.items'))->keyBy('id');
    }

    private function meetingOf(Event $event, string $title = 'Rapat pleno'): Meeting
    {
        return $this->app->make(SaveMeeting::class)->handle($event, new Meeting, [
            'title' => $title, 'meets_on' => '2026-12-01', 'starts_at' => '19:00',
        ], $this->organizer);
    }

    private function entryOf(Event $event, string $title = 'Survei lokasi'): CalendarEntry
    {
        return $this->app->make(SaveCalendarEntry::class)->handle($event, new CalendarEntry, [
            'title' => $title, 'starts_on' => '2026-12-05',
        ], $this->organizer);
    }

    public function test_my_calendar_shows_meetings_kegiatan_and_event_days_of_events_where_i_am_an_active_member(): void
    {
        $retreat = $this->makeEvent(['name' => 'Retret Pemuda', 'starts_on' => '2027-01-08', 'ends_on' => '2027-01-10']);
        $this->seat($this->person, $retreat, CommitteeRoleKind::DivisionMember, $this->makeDivision($retreat, 'Seksi Acara'));
        $meeting = $this->meetingOf($this->event);
        $entry = $this->entryOf($retreat);

        $this->actingAs($this->person)->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('Kalender Saya')
            ->assertSee(route('calendar.feed'), false)
            ->assertDontSee('data-my-calendar-scope', false)
            ->assertSee('Untuk menambah kegiatan, buka event lalu tab Kalender.');

        $items = $this->items($this->person);

        $this->assertSame(route('events.meetings.detail', [$this->event, $meeting]), $items['meeting-'.$meeting->id]['url']);
        $this->assertSame('Retret Pemuda', $items['entry-'.$entry->id]['event_name']);
        $this->assertSame(route('events.calendar', $retreat), $items['entry-'.$entry->id]['entry']['calendar_url']);
        $this->assertFalse($items['entry-'.$entry->id]['entry']['can_change']);
        $this->assertSame('Perayaan Natal 2026', $items['event-'.$this->event->id]['title']);
        $this->assertSame('Retret Pemuda', $items['event-'.$retreat->id]['title']);
    }

    public function test_events_where_i_am_only_invited_declined_or_revoked_are_left_out(): void
    {
        foreach ([MembershipStatus::Invited, MembershipStatus::Declined, MembershipStatus::Revoked] as $status) {
            $event = $this->makeEvent(['name' => 'Event '.$status->value]);
            $this->seat($this->person, $event, CommitteeRoleKind::DivisionMember, $this->makeDivision($event), accepted: false);
            $event->memberships()->where('user_id', $this->person->id)->update(['status' => $status]);
            $this->meetingOf($event, 'Rapat '.$status->value);
            $this->entryOf($event, 'Kegiatan '.$status->value);
        }

        $items = $this->items($this->person);

        $this->assertSame([$this->event->id], $items->pluck('event_id')->unique()->values()->all());
    }

    public function test_an_event_where_all_my_roles_have_ended_is_left_out(): void
    {
        $retreat = $this->makeEvent(['name' => 'Retret Pemuda', 'starts_on' => '2027-01-08', 'ends_on' => '2027-01-10']);
        $assignment = $this->seat($this->person, $retreat, CommitteeRoleKind::DivisionMember, $this->makeDivision($retreat));
        $meeting = $this->meetingOf($retreat);
        $entry = $this->entryOf($retreat);
        $this->app->make(EndAssignment::class)->handle($assignment, 'Tugas selesai', $this->organizer);

        $this->assertSame(MembershipStatus::Active, $assignment->membership->fresh()->status);
        $this->actingAs($this->person)->get(route('events.overview', $retreat))->assertNotFound();

        $ids = $this->items($this->person)->keys();

        $this->assertNotContains('meeting-'.$meeting->id, $ids);
        $this->assertNotContains('entry-'.$entry->id, $ids);
        $this->assertNotContains('event-'.$retreat->id, $ids);
        $this->assertContains('event-'.$this->event->id, $ids);
    }

    public function test_the_empty_state_shows_when_my_only_roles_have_ended(): void
    {
        $assignment = EventMembership::query()->where('user_id', $this->person->id)->sole()->activeAssignments()->sole();
        $this->app->make(EndAssignment::class)->handle($assignment, 'Tugas selesai', $this->organizer);

        $this->actingAs($this->person)->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('data-has-events="0"', false);
    }

    public function test_events_of_which_i_am_not_a_member_are_left_out(): void
    {
        $other = $this->makeEvent(['name' => 'Retret Pemuda']);
        $meeting = $this->meetingOf($other);
        $entry = $this->entryOf($other);

        $ids = $this->items($this->person)->keys();

        $this->assertNotContains('meeting-'.$meeting->id, $ids);
        $this->assertNotContains('entry-'.$entry->id, $ids);
        $this->assertNotContains('event-'.$other->id, $ids);
    }

    public function test_events_of_another_organization_are_left_out(): void
    {
        $foreign = Event::factory()->create([
            'organization_id' => Organization::factory()->create()->id,
            'starts_on' => '2026-12-10',
            'ends_on' => '2026-12-10',
        ]);
        EventMembership::factory()->create(['event_id' => $foreign->id, 'user_id' => $this->person->id]);
        $meeting = Meeting::factory()->create(['event_id' => $foreign->id, 'meets_on' => '2026-12-02']);

        $ids = $this->items($this->person)->keys();

        $this->assertNotContains('meeting-'.$meeting->id, $ids);
        $this->assertNotContains('event-'.$foreign->id, $ids);
    }

    public function test_summaries_in_my_calendar_name_the_event_of_each_item_once(): void
    {
        $meeting = $this->meetingOf($this->event);
        $entry = $this->entryOf($this->event);
        $task = $this->makeTask($this->event, ['owner_membership_id' => $this->membershipOf($this->person, $this->event)->id, 'due_on' => '2026-12-10']);

        $items = $this->items($this->person);

        foreach (['meeting-'.$meeting->id, 'entry-'.$entry->id, 'task-'.$task->id] as $id) {
            $this->assertContains(['label' => 'Event', 'value' => 'Perayaan Natal 2026'], $items[$id]['summary'], $id);
        }
        $eventDay = $items['event-'.$this->event->id];
        $this->assertSame('Perayaan Natal 2026', $eventDay['detail_title']);
        $this->assertNotContains('Event', array_column($eventDay['summary'], 'label'));
        $this->assertSame('Buka halaman rapat', $items['meeting-'.$meeting->id]['open_label']);
        $this->assertSame(['label' => 'Penanggung jawab', 'value' => 'Anggota Acara'], $items['task-'.$task->id]['summary'][2]);
    }

    public function test_only_my_own_and_collaborated_task_deadlines_are_shown(): void
    {
        $colleague = User::factory()->create();
        $this->seat($colleague, $this->event, CommitteeRoleKind::DivisionMember, $this->makeDivision($this->event, 'Seksi Konsumsi'));
        $mine = $this->makeTask($this->event, ['title' => 'Milik saya', 'owner_membership_id' => $this->membershipOf($this->person, $this->event)->id, 'due_on' => '2026-12-10']);
        $helping = $this->makeTask($this->event, ['title' => 'Saya bantu', 'owner_membership_id' => $this->membershipOf($colleague, $this->event)->id, 'due_on' => '2026-12-11'], [$this->membershipOf($this->person, $this->event)]);
        $theirs = $this->makeTask($this->event, ['title' => 'Milik rekan', 'owner_membership_id' => $this->membershipOf($colleague, $this->event)->id, 'due_on' => '2026-12-12']);

        $ids = $this->items($this->person)->keys();

        $this->assertContains('task-'.$mine->id, $ids);
        $this->assertContains('task-'.$helping->id, $ids);
        $this->assertNotContains('task-'.$theirs->id, $ids);
    }

    public function test_cancelled_events_are_left_out(): void
    {
        $meeting = $this->meetingOf($this->event);
        $this->event->forceFill(['status' => EventStatus::Cancelled])->save();

        $ids = $this->items($this->person)->keys();

        $this->assertNotContains('meeting-'.$meeting->id, $ids);
        $this->assertNotContains('event-'.$this->event->id, $ids);
    }

    public function test_the_organization_scope_needs_events_view(): void
    {
        $other = $this->makeEvent(['name' => 'Retret Pemuda']);
        $meeting = $this->meetingOf($other);
        $task = $this->makeTask($other, ['due_on' => '2026-12-10']);

        $this->feed($this->person, ['scope' => 'organization'])->assertForbidden();

        $overseer = $this->userWithPermissions('events.view');
        $this->actingAs($overseer)->get(route('calendar.index'))->assertOk()->assertSee('Semua event organisasi');

        $ids = $this->items($overseer, ['scope' => 'organization'])->keys();

        $this->assertContains('meeting-'.$meeting->id, $ids);
        $this->assertContains('event-'.$other->id, $ids);
        $this->assertContains('event-'.$this->event->id, $ids);
        $this->assertNotContains('task-'.$task->id, $ids);
    }

    public function test_the_personal_feed_rejects_an_invalid_range(): void
    {
        $this->feed($this->person, ['start' => '2026-11-01', 'end' => '2027-02-01'])->assertStatus(422);
        $this->feed($this->person, ['start' => 'kemarin', 'end' => '2026-12-01'])->assertStatus(422);
        $this->feed($this->person, ['scope' => 'semua'])->assertStatus(422);
    }

    public function test_a_person_without_events_sees_the_empty_state(): void
    {
        $newcomer = User::factory()->create();

        $this->actingAs($newcomer)->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('Anda belum menjadi panitia aktif di event mana pun.')
            ->assertSee(route('events.index'), false)
            ->assertSee('data-has-events="0"', false);

        $this->assertSame([], $this->feed($newcomer)->assertOk()->json('data.items'));
    }

    public function test_the_sidebar_links_to_my_calendar(): void
    {
        $this->actingAs($this->person)->get(route('calendar.index'))
            ->assertSee(route('calendar.index'), false)
            ->assertSee('Kalender Saya');
    }
}
