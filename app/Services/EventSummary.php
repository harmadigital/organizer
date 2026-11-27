<?php

namespace App\Services;

use App\Enums\CommitteeRoleKind;
use App\Enums\MembershipStatus;
use App\Enums\TaskStatus;
use App\Enums\WorkProgramStatus;
use App\Models\Division;
use App\Models\Document;
use App\Models\Event;
use App\Models\EventRoleAssignment;
use App\Models\Meeting;
use App\Models\MeetingActionItem;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkProgram;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * "Laporan Kondisi": the state of one event at a glance, for the core team and the
 * supervisors to read together in a routine meeting.
 *
 * Everything is read live and nothing is stored. Work is grouped by top-level division
 * (a sub-division counts towards its parent) so the picture stays at the level of the
 * whole committee. Task counts follow the "Progres Kerja" card on the overview (every
 * task that is not cancelled); percentages follow WorkProgress (finished weight of the
 * leaf tasks).
 */
class EventSummary
{
    /**
     * Label for work that belongs to no division.
     */
    public const CORE_TEAM = 'Pengurus Inti';

    /**
     * How many days ahead "segera" and the schedule look.
     */
    private const SOON_DAYS = 7;

    private const SCHEDULE_DAYS = 14;

    /**
     * Longest lists sent to the page; the counts always cover everything.
     */
    private const LIST_LIMIT = 50;

    public function __construct(
        private WorkProgress $progress,
        private EventReadiness $readiness,
        private CalendarFeed $calendar,
        private DisplayTime $time,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $viewer, Event $event): array
    {
        $event->loadMissing('venue:id,name');
        $today = CarbonImmutable::now($this->time->timezone())->startOfDay();

        $divisions = $event->divisions()->orderBy('sort_order')->orderBy('name')->get(['id', 'event_id', 'parent_id', 'name', 'sort_order']);
        $rootOf = $this->rootsOf($divisions);
        $roots = $divisions->whereNull('parent_id')->values();

        $tasks = $event->tasks()
            ->with(['owner.user:id,name', 'parent:id,title'])
            ->get(['id', 'event_id', 'parent_id', 'division_id', 'work_program_id', 'owner_membership_id', 'title', 'status', 'weight', 'due_on']);
        $counted = $tasks->reject(fn (Task $task): bool => in_array($task->status, [TaskStatus::Cancelled, TaskStatus::Draft], true))->values();
        $groupOf = fn (Task $task): string => $task->division_id === null ? 'none' : (string) ($rootOf[$task->division_id] ?? $task->division_id);
        $groupNames = $roots->mapWithKeys(fn (Division $division): array => [(string) $division->id => $division->name])->all() + ['none' => self::CORE_TEAM];

        $elapsed = $this->preparationElapsed($event, $today);
        $overall = $this->workCounts($counted, $today, $this->progress->of($tasks));

        return [
            'event' => $this->eventFacts($event, $today),
            'generated_at' => $this->time->format(now(), 'l, j F Y \p\u\k\u\l H.i'),
            'elapsed_percent' => $elapsed,
            'work' => [
                ...$overall,
                'health' => $this->health($overall['percent'], $elapsed, $overall['overdue'], $overall['open']),
            ],
            'divisions' => $this->divisionRows($roots, $counted, $tasks, $groupOf, $elapsed, $today),
            'attention' => [
                'overdue' => $this->taskRows($event, $counted->filter(fn (Task $task): bool => $task->isOverdue($today)), $groupOf, $groupNames, $today),
                'upcoming' => $this->taskRows($event, $counted->filter(fn (Task $task): bool => ! $task->status->isClosed()
                    && $task->due_on !== null
                    && $task->due_on->gte($today)
                    && $task->due_on->lt($today->addDays(self::SCHEDULE_DAYS))), $groupOf, $groupNames, $today),
            ],
            'programs' => $this->programRows($event, $tasks, $counted, $rootOf, $today),
            'meetings' => $this->meetingFacts($event, $today),
            'committee' => $this->committeeFacts($event, $roots, $counted, $groupOf),
            'schedule' => $this->schedule($viewer, $event, $today),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function eventFacts(Event $event, CarbonImmutable $today): array
    {
        $startsOn = CarbonImmutable::parse($event->starts_on->toDateString(), $this->time->timezone());
        $endsOn = CarbonImmutable::parse($event->ends_on->toDateString(), $this->time->timezone());
        $daysToGo = (int) $today->diffInDays($startsOn, false);

        return [
            'name' => $event->name,
            'code' => $event->code,
            'date_label' => $event->dateRangeLabel(),
            'status' => ['label' => $event->status->label(), 'tone' => $event->status->tone()],
            'phase' => $event->phase?->label(),
            'venue' => $event->venue?->name ?? $event->location_text,
            'target_participants' => $event->target_participants ? number_format($event->target_participants, 0, ',', '.').' orang' : null,
            'budget_target' => $event->budget_target_amount !== null ? 'Rp '.number_format($event->budget_target_amount, 0, ',', '.') : null,
            'days_to_go' => $daysToGo,
            'countdown' => match (true) {
                $daysToGo > 1 => $daysToGo.' hari lagi',
                $daysToGo === 1 => 'Besok',
                $daysToGo === 0 => 'Hari ini',
                $today->lte($endsOn) => 'Sedang berlangsung',
                default => 'Sudah lewat '.(int) $endsOn->diffInDays($today).' hari',
            },
        ];
    }

    /**
     * How far the preparation time has run, from the start of the committee (or the day the
     * event was created) to the first event day, as a whole percentage.
     */
    private function preparationElapsed(Event $event, CarbonImmutable $today): int
    {
        $start = $event->committee_starts_on !== null
            ? CarbonImmutable::parse($event->committee_starts_on->toDateString(), $this->time->timezone())
            : $this->time->toLocal($event->created_at ?? now())->startOfDay();
        $end = CarbonImmutable::parse($event->starts_on->toDateString(), $this->time->timezone());
        $length = $start->diffInDays($end, false);

        if ($length <= 0) {
            return $today->gte($end) ? 100 : 0;
        }

        return (int) max(0, min(100, floor($start->diffInDays($today, false) * 100 / $length)));
    }

    /**
     * @param  Collection<int, Task>  $counted
     * @param  array{percent: ?int, done: int, total: int}  $progress
     * @return array{percent: ?int, total: int, done: int, open: int, overdue: int, due_soon: int}
     */
    private function workCounts(Collection $counted, CarbonImmutable $today, array $progress): array
    {
        $done = $counted->filter(fn (Task $task): bool => $task->status === TaskStatus::Completed)->count();
        $overdue = $counted->filter(fn (Task $task): bool => $task->isOverdue($today))->count();

        return [
            'percent' => $progress['percent'],
            'total' => $counted->count(),
            'done' => $done,
            'open' => $counted->count() - $done,
            'overdue' => $overdue,
            'due_soon' => $counted->filter(fn (Task $task): bool => ! $task->status->isClosed()
                && $task->due_on !== null
                && $task->due_on->gte($today)
                && $task->due_on->lt($today->addDays(self::SOON_DAYS)))->count(),
        ];
    }

    /**
     * A plain verdict that compares finished work with the preparation time used.
     *
     * @return array{key: string, label: string, tone: string, reason: string}
     */
    private function health(?int $percent, int $elapsed, int $overdue, int $open): array
    {
        if ($percent === null) {
            return ['key' => 'none', 'label' => 'Belum ada tugas', 'tone' => 'secondary', 'reason' => 'Belum ada tugas yang bisa diukur.'];
        }

        $reason = 'Waktu persiapan sudah berjalan '.$elapsed.'%, pekerjaan selesai '.$percent.'%.'
            .($overdue > 0 ? ' '.$overdue.' tugas terlambat.' : '');
        $gap = $elapsed - $percent;
        $overdueShare = $open > 0 ? $overdue / $open : 0;

        return match (true) {
            $gap > 25 || $overdueShare >= 0.25 => ['key' => 'behind', 'label' => 'Tertinggal', 'tone' => 'danger', 'reason' => $reason],
            $gap > 10 || $overdue > 0 => ['key' => 'attention', 'label' => 'Perlu perhatian', 'tone' => 'warning', 'reason' => $reason],
            default => ['key' => 'ok', 'label' => 'Sesuai jadwal', 'tone' => 'success', 'reason' => $reason],
        };
    }

    /**
     * @param  Collection<int, Division>  $roots
     * @param  Collection<int, Task>  $counted
     * @param  Collection<int, Task>  $tasks
     * @param  callable(Task): string  $groupOf
     * @return list<array<string, mixed>>
     */
    private function divisionRows(Collection $roots, Collection $counted, Collection $tasks, callable $groupOf, int $elapsed, CarbonImmutable $today): array
    {
        $groups = $roots->map(fn (Division $division): array => ['key' => (string) $division->id, 'name' => $division->name]);

        if ($counted->contains(fn (Task $task): bool => $task->division_id === null)) {
            $groups->prepend(['key' => 'none', 'name' => self::CORE_TEAM]);
        }

        return $groups->map(function (array $group) use ($counted, $tasks, $groupOf, $elapsed, $today): array {
            $inGroup = $counted->filter(fn (Task $task): bool => $groupOf($task) === $group['key']);
            $counts = $this->workCounts($inGroup, $today, $this->progress->of($tasks, fn (Task $task): bool => $groupOf($task) === $group['key']));

            return [
                ...$group,
                ...$counts,
                'health' => $this->health($counts['percent'], $elapsed, $counts['overdue'], $counts['open']),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @param  callable(Task): string  $groupOf
     * @param  array<string, string>  $groupNames
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    private function taskRows(Event $event, Collection $tasks, callable $groupOf, array $groupNames, CarbonImmutable $today): array
    {
        $rows = $tasks
            ->sortBy(fn (Task $task): string => $task->due_on->toDateString().' '.str_pad((string) $task->id, 10, '0', STR_PAD_LEFT))
            ->take(self::LIST_LIMIT)
            ->map(function (Task $task) use ($event, $groupOf, $groupNames, $today): array {
                $days = (int) $today->diffInDays(CarbonImmutable::parse($task->due_on->toDateString(), $this->time->timezone()), false);

                return [
                    'title' => $task->title,
                    'parent' => $task->parent?->title,
                    'url' => route('events.tasks.detail', [$event, $task]),
                    'division_key' => $groupOf($task),
                    'division' => $groupNames[$groupOf($task)] ?? self::CORE_TEAM,
                    'owner' => $task->owner?->user?->name,
                    'due_label' => $task->due_on->locale('id')->translatedFormat('j M Y'),
                    'when' => match (true) {
                        $days < 0 => 'terlambat '.abs($days).' hari',
                        $days === 0 => 'hari ini',
                        $days === 1 => 'besok',
                        default => $days.' hari lagi',
                    },
                ];
            })
            ->values()
            ->all();

        return ['items' => $rows, 'total' => $tasks->count()];
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @param  Collection<int, Task>  $counted
     * @param  array<int, int>  $rootOf
     * @return list<array<string, mixed>>
     */
    private function programRows(Event $event, Collection $tasks, Collection $counted, array $rootOf, CarbonImmutable $today): array
    {
        return $event->programs()
            ->with(['division:id,name', 'pic.user:id,name'])
            ->where('status', '!=', WorkProgramStatus::Cancelled)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (WorkProgram $program) use ($tasks, $counted, $rootOf, $today): array {
                $own = $counted->where('work_program_id', $program->id);
                $progress = $this->progress->of($tasks, fn (Task $task): bool => $task->work_program_id === $program->id);
                $overdue = $own->filter(fn (Task $task): bool => $task->isOverdue($today))->count();
                $isPastPeriod = $program->status !== WorkProgramStatus::Completed
                    && $program->ends_on !== null
                    && $program->ends_on->lt($today)
                    && $progress['percent'] !== 100;

                return [
                    'title' => $program->title,
                    'division_key' => $program->division_id === null ? 'none' : (string) ($rootOf[$program->division_id] ?? $program->division_id),
                    'division' => $program->division?->name ?? self::CORE_TEAM,
                    'pic' => $program->pic?->user?->name,
                    'status' => ['label' => $program->status->label(), 'tone' => $program->status->tone()],
                    'period' => $this->periodLabel($program->starts_on, $program->ends_on),
                    'percent' => $progress['percent'],
                    'tasks' => $own->count(),
                    'done' => $own->where('status', TaskStatus::Completed)->count(),
                    'overdue' => $overdue,
                    'is_past_period' => $isPastPeriod,
                    'needs_attention' => $isPastPeriod || $overdue > 0,
                ];
            })
            ->sortByDesc('needs_attention')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function meetingFacts(Event $event, CarbonImmutable $today): array
    {
        $meetings = $event->meetings()
            ->withCount('attendees')
            ->orderBy('meets_on')
            ->orderBy('starts_at')
            ->get(['id', 'event_id', 'division_id', 'title', 'meets_on', 'starts_at', 'ends_at', 'location', 'minutes_version']);
        $upcoming = $meetings->filter(fn (Meeting $meeting): bool => $meeting->isUpcoming($today))->values();
        $past = $meetings->reject(fn (Meeting $meeting): bool => $meeting->isUpcoming($today))->values();
        $next = $upcoming->first();

        $actionItems = MeetingActionItem::query()
            ->where('event_id', $event->getKey())
            ->with(['task:id,event_id,title,status,due_on', 'meeting:id,title', 'owner.user:id,name'])
            ->get();
        $openItems = $actionItems
            ->filter(fn (MeetingActionItem $item): bool => $item->task !== null && ! $item->task->status->isClosed())
            ->sortBy(fn (MeetingActionItem $item): string => $item->task->due_on?->toDateString() ?? '9999-12-31')
            ->values();

        return [
            'next' => $next === null ? null : [
                'title' => $next->title,
                'url' => route('events.meetings.detail', [$event, $next]),
                'date_label' => $next->meets_on->locale('id')->translatedFormat('l, j F Y'),
                'time_label' => $next->timeLabel(),
                'location' => $next->location,
            ],
            'upcoming' => $upcoming->count(),
            'held' => $past->count(),
            'members' => $event->memberships()->where('status', MembershipStatus::Active)->count(),
            'attendance' => $past->filter(fn (Meeting $meeting): bool => $meeting->attendees_count > 0)->take(-6)->map(fn (Meeting $meeting): array => [
                'title' => $meeting->title,
                'date_label' => $meeting->meets_on->locale('id')->translatedFormat('j M'),
                'present' => $meeting->attendees_count,
            ])->values()->all(),
            'without_minutes' => $past->reject(fn (Meeting $meeting): bool => $meeting->hasMinutes())->sortByDesc(fn (Meeting $meeting): string => $meeting->meets_on->toDateString())->map(fn (Meeting $meeting): array => [
                'title' => $meeting->title,
                'url' => route('events.meetings.detail', [$event, $meeting]),
                'date_label' => $meeting->meets_on->locale('id')->translatedFormat('j M Y'),
            ])->values()->all(),
            'follow_ups' => [
                'total' => $actionItems->count(),
                'open' => $openItems->count(),
                'overdue' => $openItems->filter(fn (MeetingActionItem $item): bool => $item->task->isOverdue($today))->count(),
                'items' => $openItems->take(8)->map(fn (MeetingActionItem $item): array => [
                    'title' => $item->task->title,
                    'url' => route('events.tasks.detail', [$event, $item->task]),
                    'meeting' => $item->meeting?->title,
                    'owner' => $item->owner?->user?->name,
                    'due_label' => $item->task->due_on?->locale('id')->translatedFormat('j M Y'),
                    'is_overdue' => $item->task->isOverdue($today),
                ])->values()->all(),
            ],
        ];
    }

    /**
     * @param  Collection<int, Division>  $roots
     * @param  Collection<int, Task>  $counted
     * @param  callable(Task): string  $groupOf
     * @return array<string, mixed>
     */
    private function committeeFacts(Event $event, Collection $roots, Collection $counted, callable $groupOf): array
    {
        $statusCounts = $event->memberships()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $coordinated = EventRoleAssignment::query()
            ->active()
            ->where('event_id', $event->getKey())
            ->whereNotNull('division_id')
            ->whereHas('committeeRole', fn ($role) => $role->where('kind', CommitteeRoleKind::DivisionCoordinator))
            ->whereHas('membership', fn ($membership) => $membership->where('status', MembershipStatus::Active))
            ->pluck('division_id')
            ->unique()
            ->all();
        $groupsWithTasks = $counted->map($groupOf)->unique()->all();

        return [
            'active' => (int) ($statusCounts[MembershipStatus::Active->value] ?? 0),
            'invited' => (int) ($statusCounts[MembershipStatus::Invited->value] ?? 0),
            'missing_roles' => $this->readiness->missingRequiredRoles($event),
            'without_coordinator' => $roots->reject(fn (Division $division): bool => in_array($division->id, $coordinated, true))->pluck('name')->values()->all(),
            'without_tasks' => $roots->reject(fn (Division $division): bool => in_array((string) $division->id, $groupsWithTasks, true))->pluck('name')->values()->all(),
            'documents' => Document::query()->where('event_id', $event->getKey())->where('kind', Document::LIBRARY)->count(),
        ];
    }

    /**
     * The next two weeks from the event calendar, grouped by day.
     *
     * @return list<array{date_label: string, items: list<array<string, mixed>>}>
     */
    private function schedule(User $viewer, Event $event, CarbonImmutable $today): array
    {
        return collect($this->calendar->forEvent($viewer, $event, $today, $today->addDays(self::SCHEDULE_DAYS), null))
            ->map(fn (array $item): array => [
                'type' => $item['type'],
                'type_label' => $item['type_label'],
                'title' => $item['title'],
                'day' => max(substr($item['start'], 0, 10), $today->toDateString()),
                'time_label' => $item['all_day'] ? null : $item['time_label'],
                'url' => $item['url'],
                'tone' => $item['tone'],
                'is_done' => $item['is_done'],
            ])
            ->groupBy('day')
            ->sortKeys()
            ->map(fn (Collection $items, string $day): array => [
                'date_label' => CarbonImmutable::parse($day)->locale('id')->translatedFormat('l, j F'),
                'is_today' => $day === $today->toDateString(),
                'items' => $items->map(fn (array $item): array => array_diff_key($item, ['day' => true]))->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Top-level ancestor of every division.
     *
     * @param  Collection<int, Division>  $divisions
     * @return array<int, int>
     */
    private function rootsOf(Collection $divisions): array
    {
        $parents = $divisions->pluck('parent_id', 'id')->all();
        $roots = [];

        foreach (array_keys($parents) as $id) {
            $current = $id;
            $seen = [];

            while ($parents[$current] ?? null) {
                if (isset($seen[$current])) {
                    break;
                }

                $seen[$current] = true;
                $current = $parents[$current];
            }

            $roots[$id] = $current;
        }

        return $roots;
    }

    private function periodLabel(?CarbonInterface $startsOn, ?CarbonInterface $endsOn): ?string
    {
        $format = fn (CarbonInterface $day): string => $day->locale('id')->translatedFormat('j M Y');

        return match (true) {
            $startsOn !== null && $endsOn !== null => $format($startsOn).' – '.$format($endsOn),
            $endsOn !== null => 'Sampai '.$format($endsOn),
            $startsOn !== null => 'Mulai '.$format($startsOn),
            default => null,
        };
    }
}
