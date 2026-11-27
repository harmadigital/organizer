<?php

namespace App\Services;

use App\Enums\MembershipStatus;
use App\Models\Event;
use App\Models\EventRoleAssignment;
use App\Models\EventRoleRule;
use Illuminate\Support\Collection;

/**
 * Whether the committee is complete enough to activate the event.
 */
class EventReadiness
{
    /**
     * Every required role with the number of people who actively hold it. Only members
     * who accepted (status active) count; pending invitations do not.
     *
     * @return Collection<int, array{rule: EventRoleRule, holders: int, is_filled: bool}>
     */
    public function requiredRoles(Event $event): Collection
    {
        $rules = $event->roleRules()
            ->where('is_required', true)
            ->with('committeeRole')
            ->get()
            ->sortBy(fn (EventRoleRule $rule) => [$rule->committeeRole->sort_order, $rule->committeeRole->name])
            ->values();

        $holders = EventRoleAssignment::query()
            ->active()
            ->where('event_id', $event->getKey())
            ->whereHas('membership', fn ($membership) => $membership->where('status', MembershipStatus::Active))
            ->selectRaw('committee_role_id, count(*) as holders')
            ->groupBy('committee_role_id')
            ->pluck('holders', 'committee_role_id');

        return $rules->map(fn (EventRoleRule $rule): array => [
            'rule' => $rule,
            'holders' => (int) ($holders[$rule->committee_role_id] ?? 0),
            'is_filled' => (int) ($holders[$rule->committee_role_id] ?? 0) > 0,
        ]);
    }

    /**
     * Names of required roles that nobody actively holds yet.
     *
     * @return list<string>
     */
    public function missingRequiredRoles(Event $event): array
    {
        return $this->requiredRoles($event)
            ->reject(fn (array $requirement): bool => $requirement['is_filled'])
            ->map(fn (array $requirement): string => $requirement['rule']->committeeRole->name)
            ->values()
            ->all();
    }
}
