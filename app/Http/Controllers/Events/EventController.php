<?php

namespace App\Http\Controllers\Events;

use App\Actions\Events\ChangeEventPhase;
use App\Actions\Events\ChangeEventStatus;
use App\Actions\Events\RespondToMembership;
use App\Actions\Events\SaveEvent;
use App\Enums\EventAbility;
use App\Enums\EventPhase;
use App\Enums\EventStatus;
use App\Enums\MembershipStatus;
use App\Enums\ObjectiveType;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\ChangeEventPhaseRequest;
use App\Http\Requests\Events\ChangeEventStatusRequest;
use App\Http\Requests\Events\SaveEventRequest;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventMembership;
use App\Models\OrganizationUnit;
use App\Models\Venue;
use App\Services\CurrentOrganization;
use App\Services\DisplayTime;
use App\Services\EventAccess;
use App\Services\EventReadiness;
use App\Services\PeopleLookup;
use App\Services\TaskAuthority;
use App\Services\WorkProgress;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;

class EventController extends Controller
{
    public function __construct(private CurrentOrganization $organization) {}

    /**
     * Invitations waiting for an answer, the events the user serves in, and, for those
     * who oversee every event, the full list.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $memberships = EventMembership::query()
            ->where('user_id', $user->getKey())
            ->whereIn('status', [MembershipStatus::Invited, MembershipStatus::Active])
            ->whereHas('event', fn (Builder $event) => $event->forOrganization($this->organization->id()))
            ->with(['event.category', 'activeAssignments.committeeRole', 'activeAssignments.division'])
            ->get()
            ->sortBy(fn (EventMembership $membership) => $membership->event->starts_on)
            ->values();

        return view('events.index', [
            'invitations' => $memberships->where('status', MembershipStatus::Invited)->values(),
            'myMemberships' => $memberships->where('status', MembershipStatus::Active)->values(),
            'statuses' => EventStatus::cases(),
            'categories' => EventCategory::query()->forOrganization($this->organization->id())->orderBy('name')->get(['id', 'name', 'is_active']),
            'years' => Event::query()->forOrganization($this->organization->id())->selectRaw('distinct starts_on')->pluck('starts_on')
                ->map(fn ($date) => $date->format('Y'))->unique()->sortDesc()->values(),
            ...$this->formOptions(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Event::query()
            ->forOrganization($this->organization->id())
            ->select(['id', 'code', 'name', 'theme', 'event_category_id', 'starts_on', 'ends_on', 'status', 'phase'])
            ->with('category:id,name')
            ->withCount(['memberships as active_members_count' => fn (Builder $memberships) => $memberships->where('status', MembershipStatus::Active)]);

        $search = $request->string('search')->trim()->value();

        if ($search !== '') {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(fn (Builder $events) => $events
                ->whereRaw("name like ? escape '!'", [$pattern])
                ->orWhereRaw("code like ? escape '!'", [$pattern])
                ->orWhereRaw("theme like ? escape '!'", [$pattern]));
        }

        if ($status = EventStatus::tryFrom($request->string('status')->value())) {
            $query->where('status', $status);
        }

        if ($request->filled('event_category_id')) {
            $query->where('event_category_id', $request->integer('event_category_id'));
        }

        if ($request->filled('year')) {
            $year = $request->integer('year');
            $query->whereBetween('starts_on', ["{$year}-01-01", "{$year}-12-31"]);
        }

        if (! $request->has('order')) {
            $query->orderByDesc('starts_on');
        }

        return DataTables::eloquent($query)
            ->addColumn('category_name', fn (Event $event): ?string => $event->category?->name)
            ->addColumn('date_range', fn (Event $event): string => $event->dateRangeLabel())
            ->addColumn('status_label', fn (Event $event): string => $event->status->label())
            ->addColumn('status_tone', fn (Event $event): string => $event->status->tone())
            ->addColumn('phase_label', fn (Event $event): string => $event->phase->label())
            ->addColumn('url', fn (Event $event): string => route('events.overview', $event))
            ->removeColumn('category')
            ->make(true);
    }

    public function insert(SaveEventRequest $request, SaveEvent $saveEvent): JsonResponse
    {
        Gate::authorize('create', Event::class);

        $event = $saveEvent->handle(new Event, $request->eventAttributes(), $request->objectives(), $request->user());

        return $this->jsonSuccess(['id' => $event->getKey(), 'url' => route('events.overview', $event)], 201);
    }

    public function overview(Request $request, Event $event, EventReadiness $readiness, DisplayTime $displayTime, TaskAuthority $work, WorkProgress $progress): View
    {
        $event->load(['category', 'venue', 'unit', 'responsibleUser', 'objectives', 'statusHistories.actor']);

        $members = $event->memberships()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $requiredRoles = $readiness->requiredRoles($event);

        return view('events.overview', [
            'event' => $event,
            'requiredRoles' => $requiredRoles,
            'stats' => [
                'active_members' => (int) ($members[MembershipStatus::Active->value] ?? 0),
                'pending_invitations' => (int) ($members[MembershipStatus::Invited->value] ?? 0),
                'divisions' => $event->divisions()->count(),
                'required_filled' => $requiredRoles->where('is_filled', true)->count(),
                'required_total' => $requiredRoles->count(),
            ],
            'objectivesByType' => $event->objectives->groupBy(fn ($objective) => $objective->type->value),
            'objectiveTypes' => ObjectiveType::cases(),
            'history' => $event->statusHistories->map(fn ($entry): array => [
                'entry' => $entry,
                'at' => $displayTime->format($entry->created_at),
            ]),
            'transitions' => $event->status->allowedTransitions(),
            'phases' => EventPhase::cases(),
            'work' => $this->workSummary($request, $event, $work, $progress, $displayTime),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Progress card on the overview, for people who may see the event's work.
     *
     * @return array{progress: array{percent: ?int, done: int, total: int}, open: int, overdue: int, completed: int}|null
     */
    private function workSummary(Request $request, Event $event, TaskAuthority $work, WorkProgress $progress, DisplayTime $displayTime): ?array
    {
        if (! $work->canViewWork($request->user(), $event)) {
            return null;
        }

        $tasks = $work->visibleTasks($request->user(), $event)->get(['id', 'parent_id', 'status', 'weight', 'due_on']);
        $today = now($displayTime->timezone())->startOfDay();

        return [
            'progress' => $progress->of($tasks),
            'open' => $tasks->filter(fn ($task) => ! $task->status->isClosed())->count(),
            'overdue' => $tasks->filter(fn ($task) => $task->isOverdue($today))->count(),
            'completed' => $tasks->where('status', TaskStatus::Completed)->count(),
        ];
    }

    /**
     * The event as the edit form needs it.
     */
    public function show(Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $event->load(['objectives', 'responsibleUser:id,name']);

        return $this->jsonSuccess([
            ...$event->only([
                'id', 'code', 'name', 'theme', 'subtheme', 'event_category_id', 'venue_id', 'location_text',
                'organization_unit_id', 'responsible_user_id', 'target_participants', 'budget_target_amount',
                'description', 'background',
            ]),
            'responsible_user_id_label' => $event->responsibleUser?->name,
            'starts_on' => $event->starts_on->toDateString(),
            'ends_on' => $event->ends_on->toDateString(),
            'committee_starts_on' => $event->committee_starts_on?->toDateString(),
            'committee_ends_on' => $event->committee_ends_on?->toDateString(),
            'objectives' => $event->objectives->map(fn ($objective): array => [
                'type' => $objective->type->value,
                'description' => $objective->description,
                'target' => $objective->target,
            ]),
        ]);
    }

    public function update(SaveEventRequest $request, Event $event, SaveEvent $saveEvent): JsonResponse
    {
        Gate::authorize('update', $event);

        $saveEvent->handle($event, $request->eventAttributes(), $request->objectives(), $request->user());

        return $this->jsonSuccess(['id' => $event->getKey()]);
    }

    public function changeStatus(ChangeEventStatusRequest $request, Event $event, ChangeEventStatus $changeStatus): JsonResponse
    {
        $event = $changeStatus->handle($event, EventStatus::from($request->validated('status')), $request->validated('reason'), $request->user());

        return $this->jsonSuccess(['status' => $event->status->value]);
    }

    public function changePhase(ChangeEventPhaseRequest $request, Event $event, ChangeEventPhase $changePhase): JsonResponse
    {
        $event = $changePhase->handle($event, EventPhase::from($request->validated('phase')), $request->validated('note'), $request->user());

        return $this->jsonSuccess(['phase' => $event->phase->value]);
    }

    /**
     * People to choose from in the event form when creating or overseeing events.
     */
    public function peopleOptions(Request $request, PeopleLookup $people): JsonResponse
    {
        abort_unless($request->user()->canAny(['events.create', 'events.update']), 403);

        return $this->jsonSuccess($people->search($request->string('search')->value(), $request->integer('page', 1)));
    }

    /**
     * People to choose from inside one event: for its edit form and for appointing committee members.
     */
    public function eventPeopleOptions(Request $request, Event $event, PeopleLookup $people, EventAccess $access): JsonResponse
    {
        $permissions = $access->for($request->user(), $event);

        abort_unless(
            $request->user()->can('update', $event)
                || $permissions->has(EventAbility::CommitteeManage)
                || $permissions->hasAnywhere(EventAbility::DivisionManage),
            403,
        );

        return $this->jsonSuccess($people->search($request->string('search')->value(), $request->integer('page', 1)));
    }

    /**
     * The invited person accepts or declines their own invitation.
     */
    public function respondToInvitation(Request $request, Event $event, string $response, RespondToMembership $respond): JsonResponse
    {
        $membership = $event->memberships()
            ->where('user_id', $request->user()->getKey())
            ->where('status', MembershipStatus::Invited)
            ->first();

        abort_if($membership === null, 404);

        $respond->handle($membership, $response === 'accept', $request->user());

        return $this->jsonSuccess(['url' => $response === 'accept' ? route('events.overview', $event) : null]);
    }

    /**
     * Choices offered by the event form.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $organizationId = $this->organization->id();

        return [
            'categoryOptions' => EventCategory::query()->forOrganization($organizationId)->active()->orderBy('name')->get(['id', 'name']),
            'venueOptions' => Venue::query()->forOrganization($organizationId)->active()->orderBy('name')->get(['id', 'name', 'capacity']),
            'unitOptions' => OrganizationUnit::query()->forOrganization($organizationId)->active()->orderBy('name')->get(['id', 'name']),
            'objectiveTypes' => ObjectiveType::cases(),
        ];
    }
}
