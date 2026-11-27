<?php

namespace App\Http\Controllers\Events;

use App\Actions\Events\AssignCommitteeRole;
use App\Actions\Events\CreateDivisionsFromTemplates;
use App\Actions\Events\DeleteDivision;
use App\Actions\Events\EndAssignment;
use App\Actions\Events\RespondToMembership;
use App\Actions\Events\RevokeMembership;
use App\Actions\Events\SaveDivision;
use App\Actions\Events\UpdateAssignment;
use App\Enums\CommitteeRoleScope;
use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\AssignCommitteeRoleRequest;
use App\Http\Requests\Events\DivisionsFromTemplatesRequest;
use App\Http\Requests\Events\ReasonRequest;
use App\Http\Requests\Events\SaveDivisionRequest;
use App\Http\Requests\Events\UpdateAssignmentRequest;
use App\Models\CommitteeRole;
use App\Models\Division;
use App\Models\DivisionTemplate;
use App\Models\Event;
use App\Models\EventMembership;
use App\Models\EventRoleAssignment;
use App\Models\User;
use App\Services\CommitteeAuthority;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class CommitteeController extends Controller
{
    public function __construct(private CommitteeAuthority $authority) {}

    /**
     * The committee structure: core roles, divisions with their people, and members
     * who hold no role at the moment.
     */
    public function show(Request $request, Event $event): View
    {
        Gate::authorize('viewCommittee', $event);

        $actor = $request->user();
        $assignments = $event->assignments()
            ->active()
            ->whereHas('membership', fn ($membership) => $membership->whereIn('status', [MembershipStatus::Invited, MembershipStatus::Active]))
            ->with(['membership.user.unit', 'committeeRole', 'division'])
            ->get()
            ->each(fn (EventRoleAssignment $assignment) => $assignment->setAttribute('can_manage', $this->authority->canAssign($actor, $event, $assignment->committeeRole, $assignment->division)));

        $rules = $event->roleRules()->get()->keyBy('committee_role_id');
        $divisions = $event->divisions()->orderBy('sort_order')->orderBy('name')->get();
        $roles = CommitteeRole::query()->forOrganization($event->organization_id)->active()->orderBy('sort_order')->get();

        $membersWithoutRole = $event->memberships()
            ->whereIn('status', [MembershipStatus::Invited, MembershipStatus::Active])
            ->whereDoesntHave('activeAssignments')
            ->with('user')
            ->get();

        return view('events.committee', [
            'event' => $event,
            'coreAssignments' => $assignments->whereNull('division_id')
                ->sortBy(fn (EventRoleAssignment $assignment) => [$assignment->committeeRole->sort_order, ! $assignment->is_primary, $assignment->membership->user->name])
                ->groupBy('committee_role_id'),
            'divisionTree' => $this->tree($divisions),
            'divisionsByParent' => $divisions->groupBy(fn (Division $division) => (string) $division->parent_id),
            'divisionAssignments' => $assignments->whereNotNull('division_id')
                ->sortBy(fn (EventRoleAssignment $assignment) => [$assignment->committeeRole->sort_order, ! $assignment->is_primary, $assignment->membership->user->name])
                ->groupBy('division_id'),
            'membersWithoutRole' => $membersWithoutRole,
            'membershipAuthority' => $event->memberships()
                ->whereIn('status', [MembershipStatus::Invited, MembershipStatus::Active])
                ->get()
                ->mapWithKeys(fn (EventMembership $membership) => [$membership->getKey() => $this->authority->canManageMembership($actor, $membership)]),
            'rules' => $rules,
            'roles' => $roles,
            'assignableRoles' => $this->assignableRoles($actor, $event, $roles, $divisions, $rules),
            'divisions' => $divisions,
            'templates' => DivisionTemplate::query()->forOrganization($event->organization_id)->active()->orderBy('name')->get(['id', 'name']),
            'canManageDivisions' => $actor->can('manageDivisions', $event),
        ]);
    }

    public function assign(AssignCommitteeRoleRequest $request, Event $event, AssignCommitteeRole $assign): JsonResponse
    {
        $assignment = $assign->handle(
            $event,
            User::query()->findOrFail($request->validated('user_id')),
            $request->committeeRole(),
            $request->division(),
            $request->validated('is_primary'),
            $request->validated('job_description'),
            $request->validated('confirm_now'),
            $request->user(),
        );

        return $this->jsonSuccess(['id' => $assignment->getKey()], 201);
    }

    public function showAssignment(Request $request, Event $event, EventRoleAssignment $assignment): JsonResponse
    {
        abort_unless($this->authority->canAssign($request->user(), $event, $assignment->committeeRole, $assignment->division), 403);

        return $this->jsonSuccess([
            'id' => $assignment->getKey(),
            'person' => $assignment->membership->user->name,
            'role' => $assignment->committeeRole->name,
            'division' => $assignment->division?->name,
            'job_description' => $assignment->job_description,
            'is_primary' => $assignment->is_primary,
            'is_singleton' => (bool) $event->roleRules()->where('committee_role_id', $assignment->committee_role_id)->value('is_singleton'),
        ]);
    }

    public function updateAssignment(UpdateAssignmentRequest $request, Event $event, EventRoleAssignment $assignment, UpdateAssignment $update): JsonResponse
    {
        $update->handle($assignment, $request->validated('job_description'), $request->validated('is_primary'), $request->user());

        return $this->jsonSuccess();
    }

    public function endAssignment(ReasonRequest $request, Event $event, EventRoleAssignment $assignment, EndAssignment $end): JsonResponse
    {
        if ($assignment->membership->user_id === $request->user()->getKey()) {
            return $this->jsonFailure('Anda tidak dapat mengakhiri jabatan Anda sendiri. Minta pengurus lain untuk melakukannya.', 409);
        }

        abort_unless($assignment->isActive() && $this->authority->canAssign($request->user(), $event, $assignment->committeeRole, $assignment->division), 403);

        $end->handle($assignment, $request->validated('reason'), $request->user());

        return $this->jsonSuccess();
    }

    /**
     * Confirm that someone accepted their place, for people who cannot sign in to answer themselves.
     */
    public function confirmMembership(Request $request, Event $event, EventMembership $membership, RespondToMembership $respond): JsonResponse
    {
        abort_unless($this->authority->canManageMembership($request->user(), $membership), 403);

        $respond->handle($membership, true, $request->user());

        return $this->jsonSuccess();
    }

    public function revokeMembership(ReasonRequest $request, Event $event, EventMembership $membership, RevokeMembership $revoke): JsonResponse
    {
        if ($membership->user_id === $request->user()->getKey()) {
            return $this->jsonFailure('Anda tidak dapat mengeluarkan diri sendiri dari kepanitiaan. Minta pengurus lain untuk melakukannya.', 409);
        }

        abort_unless(
            in_array($membership->status, [MembershipStatus::Invited, MembershipStatus::Active], true)
                && $this->authority->canManageMembership($request->user(), $membership),
            403,
        );

        $revoke->handle($membership, $request->validated('reason'), $request->user());

        return $this->jsonSuccess();
    }

    public function storeDivision(SaveDivisionRequest $request, Event $event, SaveDivision $save): JsonResponse
    {
        $division = $save->handle($event, new Division, $request->divisionAttributes(), $request->user());

        return $this->jsonSuccess(['id' => $division->getKey()], 201);
    }

    public function showDivision(Event $event, Division $division): JsonResponse
    {
        Gate::authorize('manageDivisions', $event);

        return $this->jsonSuccess($division->only(['id', 'name', 'parent_id', 'division_template_id', 'description', 'responsibilities', 'sort_order']));
    }

    public function updateDivision(SaveDivisionRequest $request, Event $event, Division $division, SaveDivision $save): JsonResponse
    {
        $save->handle($event, $division, $request->divisionAttributes(), $request->user());

        return $this->jsonSuccess();
    }

    public function deleteDivision(Request $request, Event $event, Division $division, DeleteDivision $delete): JsonResponse
    {
        Gate::authorize('manageDivisions', $event);

        $delete->handle($division, $request->user());

        return $this->jsonSuccess();
    }

    public function divisionsFromTemplates(DivisionsFromTemplatesRequest $request, Event $event, CreateDivisionsFromTemplates $create): JsonResponse
    {
        $templates = DivisionTemplate::query()->whereKey($request->validated('template_ids'))->orderBy('name')->get();

        $created = $create->handle($event, $templates, $request->user());

        return $this->jsonSuccess(['created' => $created], 201);
    }

    /**
     * Divisions in display order with their depth, parents before their children.
     *
     * @param  Collection<int, Division>  $divisions
     * @return list<array{division: Division, depth: int}>
     */
    private function tree(Collection $divisions, ?int $parentId = null, int $depth = 0): array
    {
        $rows = [];

        foreach ($divisions->where('parent_id', $parentId) as $division) {
            $rows[] = ['division' => $division, 'depth' => $depth];
            $rows = [...$rows, ...$this->tree($divisions, $division->getKey(), $depth + 1)];
        }

        return $rows;
    }

    /**
     * Roles the actor may hand out, with the divisions each may be given for.
     *
     * @param  Collection<int, CommitteeRole>  $roles
     * @param  Collection<int, Division>  $divisions
     * @param  Collection<int, mixed>  $rules
     * @return list<array{id: int, name: string, scope: string, is_singleton: bool, division_ids: list<int>}>
     */
    private function assignableRoles(User $actor, Event $event, Collection $roles, Collection $divisions, Collection $rules): array
    {
        $assignable = [];

        foreach ($roles as $role) {
            $isDivisionRole = $role->scope === CommitteeRoleScope::Division;
            $divisionIds = $isDivisionRole
                ? $divisions->filter(fn (Division $division) => $this->authority->canAssign($actor, $event, $role, $division))->pluck('id')->values()->all()
                : [];

            if ($isDivisionRole ? $divisionIds === [] : ! $this->authority->canAssign($actor, $event, $role, null)) {
                continue;
            }

            $assignable[] = [
                'id' => $role->getKey(),
                'name' => $role->name,
                'scope' => $role->scope->value,
                'is_singleton' => (bool) ($rules[$role->getKey()]->is_singleton ?? $role->is_singleton_default),
                'division_ids' => $divisionIds,
            ];
        }

        return $assignable;
    }
}
