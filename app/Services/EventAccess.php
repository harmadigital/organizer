<?php

namespace App\Services;

use App\Enums\EventAbility;
use App\Models\Division;
use App\Models\Event;
use App\Models\EventMembership;
use App\Models\User;

/**
 * Resolves what a person may do inside an event.
 *
 * Access is denied by default. It comes from two places only: the abilities of the
 * committee roles the person actively holds while their membership is active, and the
 * organization permissions below, which reach every event. There is no role switching:
 * all active assignments count together.
 */
class EventAccess
{
    /**
     * Organization permissions and the event abilities they grant on every event.
     *
     * @var array<string, list<EventAbility>>
     */
    private const ORGANIZATION_GRANTS = [
        'events.view' => [
            EventAbility::EventView,
            EventAbility::CommitteeView,
            EventAbility::ProgramView,
            EventAbility::TaskView,
            EventAbility::MeetingView,
            EventAbility::CoordinationView,
            EventAbility::IssueView,
            EventAbility::DocumentView,
        ],
        'events.update' => [EventAbility::EventUpdate],
        'events.manage-committee' => [EventAbility::CommitteeManage, EventAbility::DivisionManage],
        'events.manage-work' => [EventAbility::ProgramManage, EventAbility::TaskManage],
    ];

    /**
     * @var array<string, EventPermissions>
     */
    private array $resolved = [];

    private ?int $resolvedForRequest = null;

    public function for(User $user, Event $event): EventPermissions
    {
        $this->forgetWhenTheRequestChanged();

        return $this->resolved[$user->getKey().':'.$event->getKey()] ??= $this->resolve($user, $event);
    }

    /**
     * Called after the committee changes, so the next check reads the new state.
     */
    public function forget(): void
    {
        $this->resolved = [];
    }

    private function resolve(User $user, Event $event): EventPermissions
    {
        $eventWide = [];
        $byDivision = [];
        $kinds = [];

        foreach (self::ORGANIZATION_GRANTS as $permission => $abilities) {
            if ($user->can($permission)) {
                foreach ($abilities as $ability) {
                    $eventWide[$ability->value] = true;
                }
            }
        }

        $membership = EventMembership::query()
            ->where('event_id', $event->getKey())
            ->where('user_id', $user->getKey())
            ->first();

        if ($membership?->isActive() && $user->canSignIn()) {
            $assignments = $membership->activeAssignments()->with('committeeRole.abilities')->get();
            $divisionTree = $assignments->contains(fn ($assignment) => $assignment->division_id !== null)
                ? $this->divisionTree($event)
                : [];

            foreach ($assignments as $assignment) {
                $kinds[] = $assignment->committeeRole->kind;
                $abilities = $assignment->committeeRole->abilities->pluck('ability');

                if ($assignment->division_id === null) {
                    foreach ($abilities as $ability) {
                        $eventWide[$ability] = true;
                    }

                    continue;
                }

                $reach = [$assignment->division_id, ...$this->descendantsOf($assignment->division_id, $divisionTree)];

                foreach ($abilities as $ability) {
                    $byDivision[$ability] = array_values(array_unique([...($byDivision[$ability] ?? []), ...$reach]));
                }
            }
        }

        return new EventPermissions($membership, $eventWide, $byDivision, array_values(array_unique($kinds, SORT_REGULAR)));
    }

    /**
     * @return array<int, list<int>> Children ids by parent id.
     */
    private function divisionTree(Event $event): array
    {
        $tree = [];

        foreach (Division::query()->where('event_id', $event->getKey())->whereNotNull('parent_id')->get(['id', 'parent_id']) as $division) {
            $tree[$division->parent_id][] = $division->id;
        }

        return $tree;
    }

    /**
     * @param  array<int, list<int>>  $tree
     * @return list<int>
     */
    private function descendantsOf(int $divisionId, array $tree): array
    {
        $descendants = [];
        $pending = $tree[$divisionId] ?? [];

        while ($pending !== []) {
            $childId = array_shift($pending);

            if (in_array($childId, $descendants, true)) {
                continue;
            }

            $descendants[] = $childId;
            $pending = [...$pending, ...($tree[$childId] ?? [])];
        }

        return $descendants;
    }

    /**
     * Results are kept for one request only; a long-lived container must not serve stale access.
     */
    private function forgetWhenTheRequestChanged(): void
    {
        $requestId = spl_object_id(request());

        if ($this->resolvedForRequest !== $requestId) {
            $this->resolved = [];
            $this->resolvedForRequest = $requestId;
        }
    }
}
