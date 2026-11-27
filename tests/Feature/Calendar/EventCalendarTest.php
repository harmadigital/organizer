<?php

namespace Tests\Feature\Calendar;

use App\Actions\Calendar\SaveCalendarEntry;
use App\Actions\Meetings\SaveMeeting;
use App\Actions\Meetings\SaveMinutes;
use App\Actions\Work\CompleteTask;
use App\Enums\CommitteeRoleKind;
use App\Enums\TaskStatus;
use App\Models\CalendarEntry;
use App\Models\Division;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

/**
 * The event Kalender tab: event days, meetings, task deadlines and kegiatan, read live
 * from their own tables.
 */
class EventCalendarTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    private Division $acara;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-27 10:00:00');
        $this->setUpCommittees();
        $this->event = $this->makeEvent();
        $this->acara = $this->makeDivision($this->event, 'Seksi Acara');
        $this->member = User::factory()->create(['name' => 'Anggota Acara']);
        $this->seat($this->member, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function feed(User $viewer, array $query = []): TestResponse
    {
        return $this->actingAs($viewer)->getJson(route('events.calendar.feed', [
            $this->event,
            'start' => '2026-11-30',
            'end' => '2027-01-11',
            ...$query,
        ]));
    }

    /**
     * @param  array<string, mixed>  $query
     * @return Collection<string, array<string, mixed>>
     */
    private function items(User $viewer, array $query = []): Collection
    {
        return collect($this->feed($viewer, $query)->assertOk()->json('data.items'))->keyBy('id');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeMeeting(array $attributes = []): Meeting
    {
        return $this->app->make(SaveMeeting::class)->handle($this->event, new Meeting, [
            'title' => 'Rapat pleno',
            'meets_on' => '2026-12-01',
            'starts_at' => '19:00',
            'ends_at' => '21:00',
            ...$attributes,
        ], $this->organizer);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeEntry(array $attributes = [], ?Event $event = null): CalendarEntry
    {
        return $this->app->make(SaveCalendarEntry::class)->handle($event ?? $this->event, new CalendarEntry, [
            'title' => 'Survei lokasi',
            'starts_on' => '2026-12-05',
            ...$attributes,
        ], $this->organizer);
    }

    public function test_the_calendar_tab_shows_for_a_committee_member(): void
    {
        $this->actingAs($this->member)->get(route('events.calendar', $this->event))
            ->assertOk()
            ->assertSee('>Kalender</a>', false)
            ->assertSee(route('events.calendar', $this->event), false)
            ->assertSee(route('events.calendar.feed', $this->event), false)
            ->assertSee('assets/js/organizer/calendar.js', false)
            ->assertSee('Tambah Kegiatan');

        $this->actingAs($this->member)->get(route('events.overview', $this->event))
            ->assertSee(route('events.calendar', $this->event), false);
        $this->actingAs(User::factory()->create())->get(route('events.calendar', $this->event))->assertNotFound();
    }

    public function test_the_feed_lists_meetings_task_deadlines_kegiatan_and_event_days_with_links_to_their_source(): void
    {
        $meeting = $this->makeMeeting(['location' => 'Ruang konsistori']);
        $task = $this->makeTask($this->event, ['title' => 'Pesan sound system', 'due_on' => '2026-12-10', 'division_id' => $this->acara->id]);
        $entry = $this->makeEntry(['starts_at' => '09:00', 'ends_at' => '11:00', 'location' => 'Gedung gereja']);

        $items = $this->items($this->member);

        $this->assertEqualsCanonicalizing(['event', 'meeting', 'task', 'entry'], $items->pluck('type')->unique()->values()->all());

        $meetingItem = $items['meeting-'.$meeting->id];
        $this->assertSame(route('events.meetings.detail', [$this->event, $meeting]), $meetingItem['url']);
        $this->assertSame('2026-12-01T19:00:00', $meetingItem['start']);
        $this->assertSame('2026-12-01T21:00:00', $meetingItem['end']);
        $this->assertFalse($meetingItem['all_day']);
        $this->assertSame('Pengurus Inti', $meetingItem['division']);
        $this->assertSame('Ruang konsistori', $meetingItem['location']);
        $this->assertSame('Selasa, 1 Desember 2026', $meetingItem['date_label']);

        $taskItem = $items['task-'.$task->id];
        $this->assertSame(route('events.tasks.detail', [$this->event, $task]), $taskItem['url']);
        $this->assertSame('Tenggat: Pesan sound system', $taskItem['title']);
        $this->assertSame('Pesan sound system', $taskItem['detail_title']);
        $this->assertSame(['2026-12-10', '2026-12-11', true], [$taskItem['start'], $taskItem['end'], $taskItem['all_day']]);
        $this->assertSame('Seksi Acara', $taskItem['division']);
        $this->assertSame('warning', $taskItem['tone']);

        $eventItem = $items['event-'.$this->event->id];
        $this->assertSame(route('events.overview', $this->event), $eventItem['url']);
        $this->assertSame('Hari pelaksanaan', $eventItem['title']);
        $this->assertSame($this->event->name, $eventItem['detail_title']);
        $this->assertSame(['2026-12-24', '2026-12-26'], [$eventItem['start'], $eventItem['end']]);

        $entryItem = $items['entry-'.$entry->id];
        $this->assertNull($entryItem['url']);
        $this->assertSame('09.00–11.00', $entryItem['time_label']);
        $this->assertSame($entryItem['title'], $entryItem['detail_title']);
        $this->assertFalse($entryItem['entry']['can_change']);
        $this->assertNull($entryItem['entry']['delete_url']);
    }

    public function test_a_rescheduled_meeting_moves_in_the_feed_without_a_calendar_record(): void
    {
        $meeting = $this->makeMeeting();
        $this->assertSame('2026-12-01T19:00:00', $this->items($this->member)['meeting-'.$meeting->id]['start']);

        $this->app->make(SaveMeeting::class)->handle($this->event, $meeting, ['meets_on' => '2026-12-08', 'starts_at' => '18:30'], $this->organizer);

        $this->assertSame('2026-12-08T18:30:00', $this->items($this->member)['meeting-'.$meeting->id]['start']);
        $this->assertDatabaseCount('calendar_entries', 0);
    }

    public function test_items_on_the_first_and_last_day_of_the_range_are_included_and_items_outside_are_left_out(): void
    {
        $first = $this->makeTask($this->event, ['title' => 'Hari pertama', 'due_on' => '2026-11-30']);
        $last = $this->makeTask($this->event, ['title' => 'Hari terakhir', 'due_on' => '2026-12-06']);
        $after = $this->makeTask($this->event, ['title' => 'Sesudahnya', 'due_on' => '2026-12-07']);
        $before = $this->makeTask($this->event, ['title' => 'Sebelumnya', 'due_on' => '2026-11-29']);
        $lastMeeting = $this->makeMeeting(['meets_on' => '2026-12-06']);
        $nextMeeting = $this->makeMeeting(['meets_on' => '2026-12-07']);
        $lastEntry = $this->makeEntry(['starts_on' => '2026-12-06']);
        $nextEntry = $this->makeEntry(['starts_on' => '2026-12-07']);

        $ids = $this->items($this->member, ['start' => '2026-11-30', 'end' => '2026-12-07'])->keys();

        $this->assertContains('task-'.$first->id, $ids);
        $this->assertContains('task-'.$last->id, $ids);
        $this->assertContains('meeting-'.$lastMeeting->id, $ids);
        $this->assertContains('entry-'.$lastEntry->id, $ids);
        $this->assertNotContains('task-'.$after->id, $ids);
        $this->assertNotContains('task-'.$before->id, $ids);
        $this->assertNotContains('meeting-'.$nextMeeting->id, $ids);
        $this->assertNotContains('entry-'.$nextEntry->id, $ids);
    }

    public function test_a_multi_day_event_and_a_multi_day_kegiatan_overlapping_the_range_start_are_included(): void
    {
        $entry = $this->makeEntry(['title' => 'Dekorasi', 'starts_on' => '2026-12-27', 'ends_on' => '2027-01-02']);

        $items = $this->items($this->member, ['start' => '2026-12-25', 'end' => '2027-01-04']);

        $this->assertSame(['2026-12-24', '2026-12-26'], [$items['event-'.$this->event->id]['start'], $items['event-'.$this->event->id]['end']]);
        $this->assertSame(['2026-12-27', '2027-01-03', true], [$items['entry-'.$entry->id]['start'], $items['entry-'.$entry->id]['end'], $items['entry-'.$entry->id]['all_day']]);

        $later = $this->items($this->member, ['start' => '2027-01-01', 'end' => '2027-01-08']);
        $this->assertTrue($later->has('entry-'.$entry->id));
        $this->assertFalse($later->has('event-'.$this->event->id));
    }

    public function test_cancelled_tasks_and_tasks_without_a_deadline_are_not_shown(): void
    {
        $cancelled = $this->makeTask($this->event, ['title' => 'Dibatalkan', 'due_on' => '2026-12-10']);
        $cancelled->forceFill(['status' => TaskStatus::Cancelled])->save();
        $undated = $this->makeTask($this->event, ['title' => 'Tanpa tenggat']);

        $ids = $this->items($this->member)->keys();

        $this->assertNotContains('task-'.$cancelled->id, $ids);
        $this->assertNotContains('task-'.$undated->id, $ids);
    }

    public function test_a_finished_task_is_marked_done(): void
    {
        $task = $this->makeTask($this->event, ['due_on' => '2026-12-10']);
        $this->app->make(CompleteTask::class)->handle($task, 'Sudah dipesan.', [], $this->organizer);

        $item = $this->items($this->member)['task-'.$task->id];

        $this->assertTrue($item['is_done']);
        $this->assertFalse($item['is_overdue']);
        $this->assertSame('secondary', $item['tone']);
        $this->assertSame([['label' => 'Selesai', 'tone' => 'success']], $item['badges']);
    }

    public function test_an_overdue_task_is_marked_overdue(): void
    {
        $task = $this->makeTask($this->event, ['due_on' => '2026-11-25']);

        $item = $this->items($this->member, ['start' => '2026-11-23', 'end' => '2026-11-30'])['task-'.$task->id];

        $this->assertTrue($item['is_overdue']);
        $this->assertSame('danger', $item['tone']);
        $this->assertSame([['label' => 'Terlambat', 'tone' => 'danger']], $item['badges']);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, string>
     */
    private function summaryOf(array $item): array
    {
        return collect($item['summary'])->pluck('value', 'label')->all();
    }

    public function test_a_meeting_summary_shows_when_where_who_attended_its_agenda_and_that_minutes_were_written(): void
    {
        $meeting = $this->makeMeeting([
            'meets_on' => '2026-11-25',
            'location' => 'Ruang konsistori',
            'division_id' => $this->acara->id,
            'agenda' => "Doa pembuka\n\n  Bahas peserta   <50 orang  \r\nKonsumsi & dekorasi",
        ]);
        $meeting->attendees()->attach($this->membershipOf($this->member, $this->event)->id, ['event_id' => $this->event->id]);
        $this->app->make(SaveMinutes::class)->handle($meeting, '<p>Rapat dibuka.</p>', [], [], 0, $this->organizer);

        $item = $this->items($this->member, ['start' => '2026-11-23', 'end' => '2026-11-30'])['meeting-'.$meeting->id];

        $this->assertSame([
            'Tanggal' => 'Rabu, 25 November 2026',
            'Jam' => '19.00–21.00',
            'Tempat' => 'Ruang konsistori',
            'Seksi' => 'Seksi Acara',
            'Kehadiran' => '1 orang hadir',
        ], $this->summaryOf($item));
        $this->assertSame(['label' => 'Agenda', 'text' => "Doa pembuka\nBahas peserta <50 orang\nKonsumsi & dekorasi"], $item['note']);
        $this->assertSame([['label' => 'Notulen sudah ditulis', 'tone' => 'success']], $item['badges']);
        $this->assertSame('Buka halaman rapat', $item['open_label']);
    }

    public function test_a_meeting_without_minutes_is_marked_upcoming_or_without_minutes_and_hides_attendance_until_it_is_taken(): void
    {
        $past = $this->makeMeeting(['meets_on' => '2026-11-25', 'title' => 'Rapat lalu']);
        $upcoming = $this->makeMeeting(['meets_on' => '2026-11-28', 'title' => 'Rapat besok']);

        $items = $this->items($this->member, ['start' => '2026-11-23', 'end' => '2026-11-30']);

        $this->assertSame([['label' => 'Belum ada notulen', 'tone' => 'secondary']], $items['meeting-'.$past->id]['badges']);
        $this->assertSame([['label' => 'Akan datang', 'tone' => 'primary']], $items['meeting-'.$upcoming->id]['badges']);
        $this->assertSame(['Tanggal', 'Jam', 'Seksi'], collect($items['meeting-'.$past->id]['summary'])->pluck('label')->all());
        $this->assertNull($items['meeting-'.$past->id]['note']);
    }

    public function test_a_task_summary_shows_its_program_owner_and_helpers_and_its_description_as_typed(): void
    {
        $helper = User::factory()->create(['name' => 'Budi Pembantu']);
        $this->seat($helper, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);
        $program = $this->makeProgram($this->acara, ['title' => 'Persiapan ibadah']);
        $task = $this->makeTask($this->event, [
            'title' => 'Pesan sound system',
            'division_id' => $this->acara->id,
            'work_program_id' => $program->id,
            'owner_membership_id' => $this->membershipOf($this->member, $this->event)->id,
            'due_on' => '2026-11-25',
            'description' => 'Tiket R&amp;D: tulis <nama> di kertas',
        ], [$this->membershipOf($helper, $this->event)]);

        $item = $this->items($this->member, ['start' => '2026-11-23', 'end' => '2026-11-30'])['task-'.$task->id];

        $this->assertSame([
            'Tenggat' => 'Rabu, 25 November 2026',
            'Program' => 'Persiapan ibadah',
            'Seksi' => 'Seksi Acara',
            'Penanggung jawab' => 'Anggota Acara',
            'Dikerjakan oleh' => 'Budi Pembantu',
        ], $this->summaryOf($item));
        $this->assertSame(['label' => 'Keterangan', 'text' => 'Tiket R&amp;D: tulis <nama> di kertas'], $item['note']);
        $this->assertSame([['label' => 'Terlambat', 'tone' => 'danger']], $item['badges']);
        $this->assertSame('Buka halaman tugas', $item['open_label']);
    }

    public function test_an_open_task_before_its_deadline_is_marked_not_finished_and_a_long_description_is_shortened(): void
    {
        $task = $this->makeTask($this->event, ['due_on' => '2026-12-10', 'description' => str_repeat('Siapkan kabel cadangan. ', 40)]);

        $item = $this->items($this->member)['task-'.$task->id];

        $this->assertSame([['label' => 'Belum selesai', 'tone' => 'warning']], $item['badges']);
        $this->assertLessThanOrEqual(301, mb_strlen($item['note']['text']));
        $this->assertStringEndsWith('…', $item['note']['text']);
        $this->assertStringStartsWith('Siapkan kabel cadangan. Siapkan', $item['note']['text']);
    }

    public function test_a_long_agenda_is_shortened_at_a_line_break_or_space_and_keeps_one_point_per_line(): void
    {
        $agenda = collect(range(1, 10))->map(fn (int $point): string => 'Agenda poin '.$point.': laporan dari seksi terkait kegiatan')->implode("\n");
        $meeting = $this->makeMeeting(['agenda' => $agenda]);

        $text = $this->items($this->member)['meeting-'.$meeting->id]['note']['text'];

        $this->assertGreaterThan(300, mb_strlen($agenda));
        $this->assertLessThanOrEqual(301, mb_strlen($text));
        $this->assertStringStartsWith("Agenda poin 1: laporan dari seksi terkait kegiatan\nAgenda poin 2: laporan", $text);
        $this->assertSame(5, substr_count($text, "\n"));
        $this->assertStringEndsWith('…', $text);
    }

    public function test_the_event_day_summary_shows_the_date_place_and_theme_without_the_event_name(): void
    {
        $this->event->forceFill(['theme' => 'Damai di bumi', 'location_text' => 'Gedung gereja'])->save();

        $item = $this->items($this->member)['event-'.$this->event->id];

        $this->assertSame([
            'Tanggal' => 'Kamis, 24 Desember 2026 – Jumat, 25 Desember 2026',
            'Tempat' => 'Gedung gereja',
        ], $this->summaryOf($item));
        $this->assertSame($this->event->name, $item['detail_title']);
        $this->assertSame(['label' => 'Tema', 'text' => 'Damai di bumi'], $item['note']);
        $this->assertSame([], $item['badges']);
        $this->assertSame('Buka halaman event', $item['open_label']);
    }

    public function test_a_kegiatan_summary_shows_its_details_and_author_and_has_no_page_to_open(): void
    {
        $entry = $this->makeEntry(['starts_at' => '09:00', 'location' => 'Gedung gereja', 'notes' => "Bawa meteran.\nFoto tiap ruangan."]);

        $item = $this->items($this->member)['entry-'.$entry->id];

        $this->assertSame([
            'Tanggal' => 'Sabtu, 5 Desember 2026',
            'Jam' => '09.00',
            'Tempat' => 'Gedung gereja',
            'Seksi' => 'Pengurus Inti',
            'Ditambahkan oleh' => $this->organizer->name,
        ], $this->summaryOf($item));
        $this->assertSame(['label' => 'Keterangan', 'text' => "Bawa meteran.\nFoto tiap ruangan."], $item['note']);
        $this->assertSame([], $item['badges']);
        $this->assertNull($item['open_label']);
    }

    public function test_the_number_of_feed_queries_does_not_grow_with_the_number_of_items(): void
    {
        $helper = User::factory()->create();
        $this->seat($helper, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);
        $program = $this->makeProgram($this->acara);
        $addItems = function () use ($helper, $program): void {
            $meeting = $this->makeMeeting(['division_id' => $this->acara->id]);
            $meeting->attendees()->attach($this->membershipOf($this->member, $this->event)->id, ['event_id' => $this->event->id]);
            $this->makeTask($this->event, [
                'due_on' => '2026-12-10',
                'division_id' => $this->acara->id,
                'work_program_id' => $program->id,
                'owner_membership_id' => $this->membershipOf($this->member, $this->event)->id,
            ], [$this->membershipOf($helper, $this->event)]);
            $this->makeEntry(['division_id' => $this->acara->id]);
        };
        $countQueries = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->items($this->member);
            DB::disableQueryLog();

            return count(DB::getQueryLog());
        };
        $addItems();
        $countQueries(); // the first request also loads the permission cache
        $withOne = $countQueries();
        $addItems();
        $addItems();

        $withThree = $countQueries();

        $this->assertSame($withOne, $withThree);
    }

    public function test_a_member_of_one_division_sees_items_of_every_division(): void
    {
        $konsumsi = $this->makeDivision($this->event, 'Seksi Konsumsi');
        $meeting = $this->makeMeeting(['division_id' => $konsumsi->id]);
        $task = $this->makeTask($this->event, ['division_id' => $konsumsi->id, 'due_on' => '2026-12-10']);
        $entry = $this->makeEntry(['division_id' => $konsumsi->id]);

        $ids = $this->items($this->member)->keys();

        $this->assertContains('meeting-'.$meeting->id, $ids);
        $this->assertContains('task-'.$task->id, $ids);
        $this->assertContains('entry-'.$entry->id, $ids);
    }

    public function test_the_division_filter_shows_that_division_and_its_sub_divisions_plus_event_days(): void
    {
        $drama = $this->makeDivision($this->event, 'Tim Drama', $this->acara);
        $konsumsi = $this->makeDivision($this->event, 'Seksi Konsumsi');
        $own = $this->makeEntry(['division_id' => $this->acara->id]);
        $child = $this->makeMeeting(['division_id' => $drama->id]);
        $other = $this->makeTask($this->event, ['division_id' => $konsumsi->id, 'due_on' => '2026-12-10']);
        $core = $this->makeMeeting();

        $ids = $this->items($this->member, ['division' => (string) $this->acara->id])->keys();

        $this->assertContains('entry-'.$own->id, $ids);
        $this->assertContains('meeting-'.$child->id, $ids);
        $this->assertContains('event-'.$this->event->id, $ids);
        $this->assertNotContains('task-'.$other->id, $ids);
        $this->assertNotContains('meeting-'.$core->id, $ids);
    }

    public function test_the_none_filter_shows_items_without_a_division(): void
    {
        $core = $this->makeMeeting();
        $coreTask = $this->makeTask($this->event, ['due_on' => '2026-12-10']);
        $division = $this->makeEntry(['division_id' => $this->acara->id]);

        $ids = $this->items($this->member, ['division' => 'none'])->keys();

        $this->assertContains('meeting-'.$core->id, $ids);
        $this->assertContains('task-'.$coreTask->id, $ids);
        $this->assertContains('event-'.$this->event->id, $ids);
        $this->assertNotContains('entry-'.$division->id, $ids);
    }

    public function test_a_division_of_another_event_is_rejected_as_a_filter(): void
    {
        $foreign = $this->makeDivision($this->makeEvent(['name' => 'Retret Pemuda']), 'Seksi Acara');

        $this->feed($this->member, ['division' => (string) $foreign->id])
            ->assertStatus(422)
            ->assertJsonPath('errors.division.0', 'Seksi tidak ditemukan di event ini.');
    }

    public function test_the_feed_rejects_a_range_longer_than_62_days(): void
    {
        $this->feed($this->member, ['start' => '2026-11-01', 'end' => '2027-01-15'])
            ->assertStatus(422)
            ->assertJsonPath('data', 'Rentang kalender paling lama 62 hari.');

        $this->feed($this->member, ['start' => '2026-12-01', 'end' => '2026-11-01'])->assertStatus(422);
    }

    public function test_the_program_and_tasks_page_no_longer_has_its_own_calendar(): void
    {
        $this->actingAs($this->member)->get(route('events.work', $this->event))
            ->assertOk()
            ->assertDontSee('data-view="calendar"', false)
            ->assertDontSee('data-view-panel="calendar"', false);
    }

    public function test_someone_without_access_to_the_event_gets_nothing_from_the_feed(): void
    {
        $this->makeMeeting();

        $this->actingAs(User::factory()->create())
            ->getJson(route('events.calendar.feed', [$this->event, 'start' => '2026-11-30', 'end' => '2027-01-11']))
            ->assertNotFound();
    }
}
