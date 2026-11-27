<?php

namespace App\Services;

use App\Enums\EventAbility;
use App\Enums\EventStatus;
use App\Enums\MembershipStatus;
use App\Enums\TaskStatus;
use App\Models\CalendarEntry;
use App\Models\Division;
use App\Models\Event;
use App\Models\EventMembership;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * The only place calendar items are put together, for the event Kalender tab and for
 * Kalender Saya.
 *
 * Meetings, task deadlines and event days are read live from their own tables, so a
 * rescheduled meeting or a changed deadline moves at once and nothing is ever copied. Only
 * kegiatan that committee members add by hand are stored, in calendar_entries.
 *
 * Every period is [from, until): until is the first day that is no longer included, as
 * FullCalendar sends it. Date columns are compared with ">= from" and "< until" only,
 * because SQLite stores a date cast as "Y-m-d 00:00:00".
 *
 * Every item also carries what its summary window shows before the person opens the page:
 * its own title (detail_title: the task without the grid's "Tenggat: " prefix, the event day
 * under the event's name), labelled rows (only those with a value), a plain-text note, status
 * badges and the label of the button to its page. Everything is read with eager loading, so
 * the number of queries does not grow with the number of items.
 *
 * @phpstan-type SummaryRow array{label: string, value: string}
 * @phpstan-type SummaryNote array{label: string, text: string}
 * @phpstan-type SummaryBadge array{label: string, tone: string}
 * @phpstan-type CalendarItem array{
 *     id: string,
 *     type: 'event'|'meeting'|'entry'|'task',
 *     type_label: string,
 *     title: string,
 *     detail_title: string,
 *     start: string,
 *     end: ?string,
 *     all_day: bool,
 *     url: ?string,
 *     event_id: int,
 *     event_name: string,
 *     division: string,
 *     location: ?string,
 *     time_label: string,
 *     date_label: string,
 *     is_done: bool,
 *     is_overdue: bool,
 *     tone: 'info'|'primary'|'success'|'warning'|'danger'|'secondary',
 *     summary: list<SummaryRow>,
 *     note: ?SummaryNote,
 *     badges: list<SummaryBadge>,
 *     open_label: ?string,
 *     entry: ?array{id: int, can_change: bool, show_url: ?string, update_url: ?string, delete_url: ?string, calendar_url: string}
 * }
 */
class CalendarFeed
{
    /**
     * Label for items that belong to no division.
     */
    public const CORE_TEAM = 'Pengurus Inti';

    /**
     * Order of item types on the same start.
     */
    private const TYPE_ORDER = ['event' => 0, 'meeting' => 1, 'entry' => 2, 'task' => 3];

    /**
     * Longest note excerpt in the summary window, in characters.
     */
    private const NOTE_LIMIT = 300;

    public function __construct(
        private TaskAuthority $tasks,
        private MeetingAuthority $meetings,
        private CalendarAuthority $authority,
        private DisplayTime $time,
        private CurrentOrganization $organization,
        private EventAccess $access,
    ) {}

    /**
     * The event Kalender tab. Every item of the event, optionally for one division ('none'
     * for the core team, or an id that also covers its sub-divisions). The event days are
     * always shown. Task deadlines follow TaskAuthority and meetings MeetingAuthority.
     *
     * @return list<CalendarItem>
     */
    public function forEvent(User $viewer, Event $event, CarbonImmutable $from, CarbonImmutable $until, ?string $division): array
    {
        $byDivision = $this->divisionScope($event, $division);
        $event->loadMissing('venue:id,name');
        $events = new Collection([$event->getKey() => $event]);

        $tasks = $this->tasks->visibleTasks($viewer, $event)->getQuery();
        $byDivision($tasks);

        $items = [
            ...$this->eventDays($events, $from, $until, withEventName: false),
            ...($this->meetings->canView($viewer, $event)
                ? $this->meetingItems($this->meetingQuery($events, $from, $until)->tap($byDivision), $events, withEventName: false)
                : []),
            ...$this->entryItems($this->entryQuery($events, $from, $until)->tap($byDivision), $events, $viewer, canChange: true, withEventName: false),
            ...$this->taskItems($this->taskRange($tasks, $from, $until), $events, withEventName: false),
        ];

        return $this->sorted($items);
    }

    /**
     * Kalender Saya: the event days, every meeting and every kegiatan of the events of the
     * current organization where the person is an active committee member who may still open
     * the event (cancelled events left out), plus the deadlines of tasks they own or work on.
     *
     * @return list<CalendarItem>
     */
    public function forPerson(User $viewer, CarbonImmutable $from, CarbonImmutable $until): array
    {
        $memberships = $this->activeMemberships($viewer);

        if ($memberships->isEmpty()) {
            return [];
        }

        $events = $this->events(fn (Builder $query) => $query->whereKey($memberships->pluck('event_id')->unique()->all()));
        $membershipIds = $memberships->modelKeys();

        $tasks = Task::query()
            ->whereIn('event_id', $events->modelKeys())
            ->where(fn (Builder $query) => $query
                ->whereIn('owner_membership_id', $membershipIds)
                ->orWhereHas('assignees', fn (Builder $assignees) => $assignees->whereIn('event_memberships.id', $membershipIds)));

        return $this->sorted([
            ...$this->eventDays($events, $from, $until, withEventName: true),
            ...$this->meetingItems($this->meetingQuery($events, $from, $until), $events, withEventName: true),
            ...$this->entryItems($this->entryQuery($events, $from, $until), $events, $viewer, canChange: false, withEventName: true),
            ...$this->taskItems($this->taskRange($tasks, $from, $until), $events, withEventName: true),
        ]);
    }

    /**
     * Kalender Saya for those who oversee every event (events.view): the event days,
     * meetings and kegiatan of every event of the organization except cancelled ones.
     * Task deadlines are left out so many events stay readable.
     *
     * @return list<CalendarItem>
     */
    public function forOrganization(User $viewer, CarbonImmutable $from, CarbonImmutable $until): array
    {
        $events = $this->events(fn (Builder $query) => $query);

        if ($events->isEmpty()) {
            return [];
        }

        return $this->sorted([
            ...$this->eventDays($events, $from, $until, withEventName: true),
            ...$this->meetingItems($this->meetingQuery($events, $from, $until), $events, withEventName: true),
            ...$this->entryItems($this->entryQuery($events, $from, $until), $events, $viewer, canChange: false, withEventName: true),
        ]);
    }

    /**
     * Whether the person is an active committee member of any event of the organization
     * that has not been cancelled and that they may still open.
     */
    public function hasEvents(User $viewer): bool
    {
        return $this->activeMemberships($viewer)->isNotEmpty();
    }

    /**
     * Active memberships in events the person may open, by the same rule as the event pages
     * (EventPolicy::view). A membership whose roles have all ended stays active but no longer
     * opens the event, so it is left out.
     *
     * @return Collection<int, EventMembership>
     */
    private function activeMemberships(User $viewer): Collection
    {
        return EventMembership::query()
            ->where('user_id', $viewer->getKey())
            ->where('status', MembershipStatus::Active)
            ->whereHas('event', fn (Builder $query) => $query
                ->forOrganization($this->organization->id())
                ->where('status', '!=', EventStatus::Cancelled))
            ->with('event:id')
            ->get(['id', 'event_id'])
            ->filter(fn (EventMembership $membership): bool => $this->access->for($viewer, $membership->event)->hasAnywhere(EventAbility::EventView))
            ->values();
    }

    /**
     * Events of the current organization that are not cancelled, keyed by id.
     *
     * @param  Closure(Builder<Event>): mixed  $constraint
     * @return Collection<int, Event>
     */
    private function events(Closure $constraint): Collection
    {
        $query = Event::query()
            ->forOrganization($this->organization->id())
            ->where('status', '!=', EventStatus::Cancelled);
        $constraint($query);

        return $query
            ->with('venue:id,name')
            ->get(['id', 'organization_id', 'venue_id', 'name', 'code', 'theme', 'description', 'location_text', 'starts_on', 'ends_on', 'status'])
            ->keyBy('id');
    }

    /**
     * Narrows a meeting, task or kegiatan query to the chosen division filter.
     *
     * @return Closure(Builder<*>): void
     */
    private function divisionScope(Event $event, ?string $division): Closure
    {
        if ($division === null || $division === '') {
            return function (Builder $query): void {};
        }

        if ($division === 'none') {
            return function (Builder $query): void {
                $query->whereNull($query->qualifyColumn('division_id'));
            };
        }

        $selected = $event->divisions()->whereKey((int) $division)->first();
        $divisionIds = $selected === null ? [0] : [$selected->getKey(), ...$selected->descendantIds()];

        return function (Builder $query) use ($divisionIds): void {
            $query->whereIn($query->qualifyColumn('division_id'), $divisionIds);
        };
    }

    /**
     * @param  Collection<int, Event>  $events
     * @return list<CalendarItem>
     */
    private function eventDays(Collection $events, CarbonImmutable $from, CarbonImmutable $until, bool $withEventName): array
    {
        return $events
            ->filter(fn (Event $event) => $event->starts_on->lt($until) && $event->ends_on->gte($from))
            ->map(fn (Event $event) => $this->item(
                id: 'event-'.$event->getKey(),
                type: 'event',
                typeLabel: 'Hari pelaksanaan',
                title: $withEventName ? $event->name : 'Hari pelaksanaan',
                detailTitle: $event->name,
                start: $event->starts_on->toDateString(),
                end: $event->ends_on->copy()->addDay()->toDateString(),
                allDay: true,
                url: route('events.overview', $event),
                event: $event,
                division: '',
                location: null,
                timeLabel: 'Seharian',
                dateLabel: $this->dateLabel($event->starts_on, $event->ends_on),
                tone: 'info',
                summary: $this->rows([
                    'Tanggal' => $this->dateLabel($event->starts_on, $event->ends_on),
                    'Tempat' => $event->venue?->name ?? $event->location_text,
                ]),
                note: filled($event->theme)
                    ? $this->note('Tema', $event->theme)
                    : $this->note('Keterangan', $event->description),
                openLabel: 'Buka halaman event',
            ))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Event>  $events
     * @return Builder<Meeting>
     */
    private function meetingQuery(Collection $events, CarbonImmutable $from, CarbonImmutable $until): Builder
    {
        return Meeting::query()
            ->whereIn('event_id', $events->modelKeys())
            ->where('meets_on', '>=', $from->toDateString())
            ->where('meets_on', '<', $until->toDateString())
            ->select(['id', 'event_id', 'division_id', 'title', 'meets_on', 'starts_at', 'ends_at', 'location', 'agenda', 'minutes_version'])
            ->withCount('attendees')
            ->with('division:id,name');
    }

    /**
     * @param  Builder<Meeting>  $query
     * @param  Collection<int, Event>  $events
     * @return list<CalendarItem>
     */
    private function meetingItems(Builder $query, Collection $events, bool $withEventName): array
    {
        $today = $this->today();

        return $query->get()->map(function (Meeting $meeting) use ($events, $today, $withEventName): array {
            $event = $events[$meeting->event_id];
            $day = $meeting->meets_on->toDateString();
            $division = $meeting->division?->name ?? self::CORE_TEAM;

            return $this->item(
                id: 'meeting-'.$meeting->getKey(),
                type: 'meeting',
                typeLabel: 'Rapat',
                title: $meeting->title,
                start: $this->moment($day, $meeting->starts_at),
                end: $meeting->ends_at ? $this->moment($day, $meeting->ends_at) : null,
                allDay: false,
                url: route('events.meetings.detail', [$event, $meeting]),
                event: $event,
                division: $division,
                location: $meeting->location,
                timeLabel: $meeting->timeLabel(),
                dateLabel: $this->dateLabel($meeting->meets_on),
                tone: 'primary',
                summary: $this->rows([
                    'Tanggal' => $this->dateLabel($meeting->meets_on),
                    'Jam' => $meeting->timeLabel(),
                    'Tempat' => $meeting->location,
                    'Seksi' => $division,
                    'Event' => $withEventName ? $event->name : null,
                    'Kehadiran' => $meeting->attendees_count > 0 ? $meeting->attendees_count.' orang hadir' : null,
                ]),
                note: $this->note('Agenda', $meeting->agenda),
                badges: [match (true) {
                    $meeting->hasMinutes() => ['label' => 'Notulen sudah ditulis', 'tone' => 'success'],
                    $meeting->isUpcoming($today) => ['label' => 'Akan datang', 'tone' => 'primary'],
                    default => ['label' => 'Belum ada notulen', 'tone' => 'secondary'],
                }],
                openLabel: 'Buka halaman rapat',
            );
        })->all();
    }

    /**
     * @param  Collection<int, Event>  $events
     * @return Builder<CalendarEntry>
     */
    private function entryQuery(Collection $events, CarbonImmutable $from, CarbonImmutable $until): Builder
    {
        return CalendarEntry::query()
            ->whereIn('event_id', $events->modelKeys())
            ->where('starts_on', '<', $until->toDateString())
            ->where('ends_on', '>=', $from->toDateString())
            ->with(['division:id,name', 'author:id,name']);
    }

    /**
     * @param  Builder<CalendarEntry>  $query
     * @param  Collection<int, Event>  $events
     * @return list<CalendarItem>
     */
    private function entryItems(Builder $query, Collection $events, User $viewer, bool $canChange, bool $withEventName): array
    {
        return $query->get()->map(function (CalendarEntry $entry) use ($events, $viewer, $canChange, $withEventName): array {
            $event = $events[$entry->event_id];
            $entry->setRelation('event', $event);
            $mayChange = $canChange && $this->authority->canChange($viewer, $entry);
            $startsOn = $entry->starts_on->toDateString();
            $division = $entry->division?->name ?? self::CORE_TEAM;
            $author = $entry->author?->name ?? '-';

            return $this->item(
                id: 'entry-'.$entry->getKey(),
                type: 'entry',
                typeLabel: 'Kegiatan',
                title: $entry->title,
                start: $entry->isAllDay() ? $startsOn : $this->moment($startsOn, $entry->starts_at),
                end: match (true) {
                    $entry->isAllDay() => $entry->ends_on->copy()->addDay()->toDateString(),
                    $entry->ends_at !== null => $this->moment($startsOn, $entry->ends_at),
                    default => null,
                },
                allDay: $entry->isAllDay(),
                url: null,
                event: $event,
                division: $division,
                location: $entry->location,
                timeLabel: $entry->timeLabel(),
                dateLabel: $this->dateLabel($entry->starts_on, $entry->ends_on),
                tone: 'success',
                summary: $this->rows([
                    'Tanggal' => $this->dateLabel($entry->starts_on, $entry->ends_on),
                    'Jam' => $entry->timeLabel(),
                    'Tempat' => $entry->location,
                    'Seksi' => $division,
                    'Event' => $withEventName ? $event->name : null,
                    'Ditambahkan oleh' => $author,
                ]),
                note: $this->note('Keterangan', $entry->notes),
                entry: [
                    'id' => $entry->getKey(),
                    'can_change' => $mayChange,
                    'show_url' => $mayChange ? route('events.calendar.show', [$event, $entry]) : null,
                    'update_url' => $mayChange ? route('events.calendar.update', [$event, $entry]) : null,
                    'delete_url' => $mayChange ? route('events.calendar.delete', [$event, $entry]) : null,
                    'calendar_url' => route('events.calendar', $event),
                ],
            );
        })->all();
    }

    /**
     * Tasks with a deadline inside the period. Cancelled tasks and tasks without a deadline
     * are left out; finished ones stay, marked as done.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    private function taskRange(Builder $query, CarbonImmutable $from, CarbonImmutable $until): Builder
    {
        return $query
            ->where('tasks.status', '!=', TaskStatus::Cancelled)
            ->whereNotNull('tasks.due_on')
            ->where('tasks.due_on', '>=', $from->toDateString())
            ->where('tasks.due_on', '<', $until->toDateString())
            ->select(['tasks.id', 'tasks.event_id', 'tasks.division_id', 'tasks.work_program_id', 'tasks.owner_membership_id', 'tasks.title', 'tasks.description', 'tasks.status', 'tasks.due_on'])
            ->with([
                'division:id,name',
                'program:id,title',
                'owner:id,user_id',
                'owner.user:id,name',
                'assignees:event_memberships.id,event_memberships.user_id',
                'assignees.user:id,name',
            ]);
    }

    /**
     * @param  Builder<Task>  $query
     * @param  Collection<int, Event>  $events
     * @return list<CalendarItem>
     */
    private function taskItems(Builder $query, Collection $events, bool $withEventName): array
    {
        $today = $this->today();

        return $query->get()->map(function (Task $task) use ($events, $today, $withEventName): array {
            $event = $events[$task->event_id];
            $isDone = $task->status === TaskStatus::Completed;
            $isOverdue = $task->isOverdue($today);
            $division = $task->division?->name ?? self::CORE_TEAM;

            return $this->item(
                id: 'task-'.$task->getKey(),
                type: 'task',
                typeLabel: 'Tenggat tugas',
                title: 'Tenggat: '.$task->title,
                detailTitle: $task->title,
                start: $task->due_on->toDateString(),
                end: $task->due_on->copy()->addDay()->toDateString(),
                allDay: true,
                url: route('events.tasks.detail', [$event, $task]),
                event: $event,
                division: $division,
                location: null,
                timeLabel: 'Seharian',
                dateLabel: $this->dateLabel($task->due_on),
                tone: match (true) {
                    $isDone => 'secondary',
                    $isOverdue => 'danger',
                    default => 'warning',
                },
                isDone: $isDone,
                isOverdue: $isOverdue,
                summary: $this->rows([
                    'Tenggat' => $this->dateLabel($task->due_on),
                    'Program' => $task->program?->title,
                    'Seksi' => $division,
                    'Penanggung jawab' => $task->owner?->user?->name,
                    'Dikerjakan oleh' => $task->assignees
                        ->map(fn (EventMembership $assignee): ?string => $assignee->user?->name)
                        ->filter()
                        ->sort()
                        ->implode(', '),
                    'Event' => $withEventName ? $event->name : null,
                ]),
                note: $this->note('Keterangan', $task->description),
                badges: [match (true) {
                    $isDone => ['label' => 'Selesai', 'tone' => 'success'],
                    $isOverdue => ['label' => 'Terlambat', 'tone' => 'danger'],
                    default => ['label' => 'Belum selesai', 'tone' => 'warning'],
                }],
                openLabel: 'Buka halaman tugas',
            );
        })->all();
    }

    /**
     * @param  list<SummaryRow>  $summary
     * @param  SummaryNote|null  $note
     * @param  list<SummaryBadge>  $badges
     * @param  array{id: int, can_change: bool, show_url: ?string, update_url: ?string, delete_url: ?string, calendar_url: string}|null  $entry
     * @return CalendarItem
     */
    private function item(
        string $id,
        string $type,
        string $typeLabel,
        string $title,
        string $start,
        ?string $end,
        bool $allDay,
        ?string $url,
        Event $event,
        string $division,
        ?string $location,
        string $timeLabel,
        string $dateLabel,
        string $tone,
        bool $isDone = false,
        bool $isOverdue = false,
        array $summary = [],
        ?array $note = null,
        array $badges = [],
        ?string $openLabel = null,
        ?array $entry = null,
        ?string $detailTitle = null,
    ): array {
        return [
            'id' => $id,
            'type' => $type,
            'type_label' => $typeLabel,
            'title' => $title,
            'detail_title' => $detailTitle ?? $title,
            'start' => $start,
            'end' => $end,
            'all_day' => $allDay,
            'url' => $url,
            'event_id' => $event->getKey(),
            'event_name' => $event->name,
            'division' => $division,
            'location' => $location,
            'time_label' => $timeLabel,
            'date_label' => $dateLabel,
            'is_done' => $isDone,
            'is_overdue' => $isOverdue,
            'tone' => $tone,
            'summary' => $summary,
            'note' => $note,
            'badges' => $badges,
            'open_label' => $url === null ? null : $openLabel,
            'entry' => $entry,
        ];
    }

    /**
     * Summary rows in the given order, leaving out those without a value.
     *
     * @param  array<string, ?string>  $values
     * @return list<SummaryRow>
     */
    private function rows(array $values): array
    {
        $rows = [];

        foreach ($values as $label => $value) {
            if (filled($value)) {
                $rows[] = ['label' => $label, 'value' => $value];
            }
        }

        return $rows;
    }

    /**
     * A short excerpt of a plain-text field (agenda, task description, keterangan, event
     * description): spaces collapsed and empty lines dropped, line breaks kept (an agenda is one
     * point per line). The text is kept as typed, "<" and "&" included; the page sets it with
     * textContent. Null when nothing is left.
     *
     * @return SummaryNote|null
     */
    private function note(string $label, ?string $text): ?array
    {
        $lines = array_filter(array_map(
            fn (string $line): string => trim((string) preg_replace('/\h+/u', ' ', $line)),
            preg_split('/\R/u', (string) $text) ?: [],
        ), fn (string $line): bool => $line !== '');

        if ($lines === []) {
            return null;
        }

        return ['label' => $label, 'text' => $this->shortened(implode("\n", $lines))];
    }

    /**
     * Cut to NOTE_LIMIT characters at the last space or line break, with "…". Unlike
     * Str::limit with preserveWords, line breaks survive.
     */
    private function shortened(string $text): string
    {
        if (mb_strlen($text) <= self::NOTE_LIMIT) {
            return $text;
        }

        $cut = mb_substr($text, 0, self::NOTE_LIMIT);

        if (preg_match('/^\s/u', mb_substr($text, self::NOTE_LIMIT, 1)) !== 1) {
            $cut = (string) preg_replace('/\s+\S*$/u', '', $cut);
        }

        return rtrim($cut).'…';
    }

    /**
     * Today in the organization's timezone.
     */
    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now($this->time->timezone())->startOfDay();
    }

    /**
     * "2026-12-01T19:00:00": a wall-clock time in the organization's timezone, without offset.
     */
    private function moment(string $day, string $time): string
    {
        return $day.'T'.substr($time, 0, 5).':00';
    }

    /**
     * "Selasa, 1 Desember 2026", or "Kamis, 24 Desember 2026 – Sabtu, 26 Desember 2026" for several days.
     */
    private function dateLabel(CarbonInterface $startsOn, ?CarbonInterface $endsOn = null): string
    {
        $format = fn (CarbonInterface $day): string => $day->locale('id')->translatedFormat('l, j F Y');

        return $endsOn === null || $endsOn->isSameDay($startsOn)
            ? $format($startsOn)
            : $format($startsOn).' – '.$format($endsOn);
    }

    /**
     * Sorted by start, then event days, meetings, kegiatan and tasks, then title.
     *
     * @param  list<CalendarItem>  $items
     * @return list<CalendarItem>
     */
    private function sorted(array $items): array
    {
        usort($items, fn (array $a, array $b): int => [$a['start'], self::TYPE_ORDER[$a['type']], $a['title']]
            <=> [$b['start'], self::TYPE_ORDER[$b['type']], $b['title']]);

        return $items;
    }
}
