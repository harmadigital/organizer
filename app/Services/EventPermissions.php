<?php

namespace App\Services;

use App\Enums\CommitteeRoleKind;
use App\Enums\EventAbility;
use App\Models\EventMembership;

/**
 * What one person may do in one event, resolved by EventAccess.
 *
 * Abilities from roles scoped to the whole event, and those the person's organization
 * permissions grant, apply everywhere in the event. Abilities from a role scoped to a
 * division apply to that division and the divisions below it only.
 */
final class EventPermissions
{
    /**
     * @param  array<string, true>  $eventWide
     * @param  array<string, list<int>>  $byDivision
     * @param  list<CommitteeRoleKind>  $kinds
     */
    public function __construct(
        public readonly ?EventMembership $membership,
        private readonly array $eventWide,
        private readonly array $byDivision,
        private readonly array $kinds,
    ) {}

    /**
     * Without a division: only abilities that reach the whole event count.
     * With a division: abilities scoped to that division count as well.
     */
    public function has(EventAbility $ability, ?int $divisionId = null): bool
    {
        if (isset($this->eventWide[$ability->value])) {
            return true;
        }

        return $divisionId !== null && in_array($divisionId, $this->byDivision[$ability->value] ?? [], true);
    }

    /**
     * Whether the ability is held somewhere in the event, for the whole event or for a division.
     */
    public function hasAnywhere(EventAbility $ability): bool
    {
        return isset($this->eventWide[$ability->value]) || ($this->byDivision[$ability->value] ?? []) !== [];
    }

    /**
     * Divisions where the ability is held through a division-scoped role.
     *
     * @return list<int>
     */
    public function divisionsWith(EventAbility $ability): array
    {
        return $this->byDivision[$ability->value] ?? [];
    }

    public function holdsKind(CommitteeRoleKind $kind): bool
    {
        return in_array($kind, $this->kinds, true);
    }

    public function isActiveMember(): bool
    {
        return $this->membership?->isActive() === true;
    }
}
