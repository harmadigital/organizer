<?php

namespace App\Http\Controllers\Events;

use App\Actions\Work\DeleteWorkProgram;
use App\Actions\Work\SaveWorkProgram;
use App\Enums\MembershipStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\WorkProgramStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Work\SaveWorkProgramRequest;
use App\Models\Event;
use App\Models\EventMembership;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkProgram;
use App\Services\DisplayTime;
use App\Services\TaskAuthority;
use App\Services\WorkProgress;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The "Program & Tugas" tab: the person's own tasks, programs with their tasks, and a calendar. The page loads its data from data() and redraws it after
 * every change.
 */
class WorkController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * Label for programs and tasks that belong to no division.
     */
    public const CORE_TEAM = 'Pengurus Inti';

    public function __construct(
        private TaskAuthority $authority,
        private WorkProgress $progress,
        private DisplayTime $time,
    ) {}

    public function index(Request $request, Event $event): View
    {
        Gate::authorize('viewWork', $event);

        $actor = $request->user();

        return view('events.work', [
            'event' => $event,
            'divisions' => $event->divisions()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'parent_id']),
            'priorities' => TaskPriority::cases(),
            'programStatuses' => WorkProgramStatus::cases(),
            'canCreateTasks' => $this->authority->canCreateTasks($actor, $event) || $this->authority->canCreateOwnTasks($actor, $event),
            'canCreatePrograms' => $this->authority->canCreatePrograms($actor, $event),
            'selfOnlyMembership' => $this->selfOnlyMembership($actor, $event),
        ]);
    }

    /**
     * Everything the page draws: programs with their progress, tasks, and the summary cards.
     */
    public function data(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('viewWork', $event);

        $actor = $request->user();
        $today = now($this->time->timezone())->startOfDay();
        $membership = $event->memberships()->where('user_id', $actor->getKey())->where('status', MembershipStatus::Active)->first();

        $tasks = $this->authority->visibleTasks($actor, $event)
            ->with(['division:id,name', 'program:id,title', 'owner.user:id,name', 'assignees.user:id,name'])
            ->withCount('comments')
            ->withCount(['dependencies as open_dependencies_count' => fn (Builder $dependency) => $dependency->whereNotIn('tasks.status', [TaskStatus::Completed, TaskStatus::Cancelled])])
            ->orderByRaw('case when due_on is null then 1 else 0 end')
            ->orderBy('due_on')
            ->orderBy('id')
            ->get()
            ->each(fn (Task $task) => $task->setRelation('event', $event));

        $programs = $this->authority->visiblePrograms($actor, $event)
            ->with(['division:id,name', 'pic.user:id,name'])
            ->orderByRaw('case when division_id is null then 0 else 1 end')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->each(fn (WorkProgram $program) => $program->setRelation('event', $event));

        $programProgress = $this->progress->groupedBy($tasks, 'work_program_id');
        $subtaskProgress = $this->progress->groupedBy($tasks, 'parent_id');
        $childCounts = $tasks->whereNotNull('parent_id')->countBy('parent_id');

        return $this->jsonSuccess([
            'today' => $today->toDateString(),
            'can_create_tasks' => $this->authority->canCreateTasks($actor, $event) || $this->authority->canCreateOwnTasks($actor, $event),
            'summary' => [
                'progress' => $this->progress->of($tasks),
                'open' => $tasks->filter(fn (Task $task) => ! $task->status->isClosed())->count(),
                'overdue' => $tasks->filter(fn (Task $task) => $task->isOverdue($today))->count(),
                'completed' => $tasks->where('status', TaskStatus::Completed)->count(),
            ],
            'programs' => $programs->map(fn (WorkProgram $program): array => [
                'id' => $program->getKey(),
                'title' => $program->title,
                'division_id' => $program->division_id,
                'division' => $program->division?->name ?? self::CORE_TEAM,
                'pic' => $program->pic?->user->name,
                'period' => $this->period($program->starts_on, $program->ends_on),
                'status' => ['value' => $program->status->value, 'label' => $program->status->label(), 'tone' => $program->status->tone()],
                'progress' => $programProgress[$program->getKey()] ?? ['percent' => null, 'done' => 0, 'total' => 0],
                'task_count' => $tasks->where('work_program_id', $program->getKey())->count(),
                'can_manage' => $this->authority->canManageProgram($actor, $program),
                'can_add_tasks' => $this->authority->canManageTasksIn($actor, $event, $program->division_id)
                    || ($program->division_id === null && $this->authority->canCreateOwnTasks($actor, $event)),
                'pic_membership_id' => $program->pic_membership_id,
            ])->values(),
            'tasks' => $tasks->map(fn (Task $task): array => [
                'id' => $task->getKey(),
                'title' => $task->title,
                'parent_id' => $task->parent_id,
                'division_id' => $task->division_id,
                'division' => $task->division?->name ?? self::CORE_TEAM,
                'work_program_id' => $task->work_program_id,
                'program' => $task->program?->title,
                'owner' => $task->owner?->user->name,
                'assignees' => $task->assignees->map(fn (EventMembership $assignee) => $assignee->user->name)->values(),
                'is_mine' => $membership !== null && $task->isWorkedOnBy($membership),
                'priority' => ['value' => $task->priority->value, 'label' => $task->priority->label(), 'tone' => $task->priority->tone()],
                'status' => ['value' => $task->status->value, 'label' => $task->status->label(), 'tone' => $task->status->tone()],
                'starts_on' => $task->starts_on?->toDateString(),
                'due_on' => $task->due_on?->toDateString(),
                'due_label' => $task->due_on?->locale('id')->translatedFormat('j M Y'),
                'is_overdue' => $task->isOverdue($today),
                'result' => $task->result,
                'weight' => $task->weight,
                'subtasks' => $childCounts[$task->getKey()] ?? 0,
                'subtask_progress' => $subtaskProgress[$task->getKey()] ?? null,
                'open_dependencies' => $task->open_dependencies_count,
                'can_complete' => $this->authority->canComplete($actor, $task),
                'comments' => $task->comments_count,
                'url' => route('events.tasks.detail', [$event, $task]),
            ])->values(),
        ]);
    }

    /**
     * Committee members of the event for owner, collaborator and program PIC dropdowns.
     */
    public function memberOptions(Request $request, Event $event): JsonResponse
    {
        abort_unless($this->mayPlan($request, $event), 403);

        $query = $event->memberships()
            ->whereIn('event_memberships.status', [MembershipStatus::Invited, MembershipStatus::Active])
            ->join('users', 'users.id', '=', 'event_memberships.user_id')
            ->select('event_memberships.*')
            ->with(['user:id,name', 'activeAssignments.committeeRole:id,name', 'activeAssignments.division:id,name'])
            ->orderBy('users.name')
            ->orderBy('event_memberships.id');

        $this->search($query, 'users.name', $request->string('search')->value());

        return $this->jsonSuccess($this->page($query, $request, fn (EventMembership $membership): array => [
            'id' => $membership->getKey(),
            'text' => $membership->user->name,
            'description' => $membership->activeAssignments
                ->map(fn ($assignment) => trim($assignment->committeeRole->name.' '.($assignment->division?->name ?? '')))
                ->implode(', ').($membership->status === MembershipStatus::Invited ? ' (diundang)' : ''),
        ]));
    }

    /**
     * Tasks of the event for the parent and prerequisite dropdowns.
     */
    public function taskOptions(Request $request, Event $event): JsonResponse
    {
        abort_unless($this->mayPlan($request, $event), 403);

        $query = $event->tasks()->with('division:id,name')->orderBy('title')->orderBy('id');

        if ($request->boolean('top_level')) {
            $query->whereNull('parent_id');
        }

        if ($request->filled('exclude')) {
            $query->whereKeyNot($request->integer('exclude'));
        }

        $this->search($query, 'title', $request->string('search')->value());

        return $this->jsonSuccess($this->page($query, $request, fn (Task $task): array => [
            'id' => $task->getKey(),
            'text' => $task->title,
            'description' => implode(' · ', array_filter([$task->division?->name ?? self::CORE_TEAM, $task->status->label()])),
        ]));
    }

    /**
     * Programs of the event, optionally of one division, for the task form.
     */
    public function programOptions(Request $request, Event $event): JsonResponse
    {
        abort_unless($this->mayPlan($request, $event), 403);

        $query = $event->programs()->with('division:id,name')->orderBy('title')->orderBy('id');

        if ($request->boolean('core')) {
            $query->whereNull('division_id');
        } elseif ($request->filled('division_id')) {
            $query->where('division_id', $request->integer('division_id'));
        }

        $this->search($query, 'title', $request->string('search')->value());

        return $this->jsonSuccess($this->page($query, $request, fn (WorkProgram $program): array => [
            'id' => $program->getKey(),
            'text' => $program->title,
            'description' => $program->division?->name ?? self::CORE_TEAM,
        ]));
    }

    public function storeProgram(SaveWorkProgramRequest $request, Event $event, SaveWorkProgram $save): JsonResponse
    {
        $program = $save->handle($event, new WorkProgram, $request->programAttributes(), $request->user());

        return $this->jsonSuccess(['id' => $program->getKey()], 201);
    }

    public function showProgram(Request $request, Event $event, WorkProgram $program): JsonResponse
    {
        abort_unless($this->authority->canManageProgram($request->user(), $program), 403);

        return $this->jsonSuccess([
            ...$program->only(['id', 'division_id', 'title', 'objective', 'output', 'indicator', 'pic_membership_id', 'sort_order']),
            'status' => $program->status->value,
            'starts_on' => $program->starts_on?->toDateString(),
            'ends_on' => $program->ends_on?->toDateString(),
            'pic_membership_id_label' => $program->pic?->user->name,
        ]);
    }

    public function updateProgram(SaveWorkProgramRequest $request, Event $event, WorkProgram $program, SaveWorkProgram $save): JsonResponse
    {
        $save->handle($event, $program, $request->programAttributes(), $request->user());

        return $this->jsonSuccess();
    }

    public function deleteProgram(Request $request, Event $event, WorkProgram $program, DeleteWorkProgram $delete): JsonResponse
    {
        abort_unless($this->authority->canManageProgram($request->user(), $program), 403);

        $delete->handle($program, $request->user());

        return $this->jsonSuccess();
    }

    /**
     * Whoever may add tasks or programs may search the dropdowns of the forms.
     */
    private function mayPlan(Request $request, Event $event): bool
    {
        return $this->authority->canCreateTasks($request->user(), $event)
            || $this->authority->canCreatePrograms($request->user(), $event)
            || $this->authority->canCreateOwnTasks($request->user(), $event);
    }

    /**
     * For someone who may only add tasks for themselves, the membership the form fills in.
     *
     * @return array{id: int, name: string}|null
     */
    private function selfOnlyMembership(User $actor, Event $event): ?array
    {
        if ($this->authority->canCreateTasks($actor, $event) || ! $this->authority->canCreateOwnTasks($actor, $event)) {
            return null;
        }

        return ['id' => $this->authority->ownMembershipId($actor, $event), 'name' => $actor->name];
    }

    private function period(?CarbonInterface $startsOn, ?CarbonInterface $endsOn): ?string
    {
        $format = fn (CarbonInterface $date): string => $date->locale('id')->translatedFormat('j M Y');

        return match (true) {
            $startsOn !== null && $endsOn !== null => $format($startsOn).' – '.$format($endsOn),
            $startsOn !== null => 'Mulai '.$format($startsOn),
            $endsOn !== null => 'Sampai '.$format($endsOn),
            default => null,
        };
    }

    private function search(Builder|HasMany $query, string $column, string $term): void
    {
        $term = trim($term);

        if ($term !== '') {
            $query->whereRaw($column." like ? escape '!'", ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%']);
        }
    }

    /**
     * @return array{results: list<array<string, mixed>>, more: bool}
     */
    private function page(Builder|HasMany $query, Request $request, callable $map): array
    {
        $rows = $query->forPage(max(1, $request->integer('page', 1)), self::PER_PAGE + 1)->get();

        return [
            'results' => $rows->take(self::PER_PAGE)->map($map)->values()->all(),
            'more' => $rows->count() > self::PER_PAGE,
        ];
    }
}
