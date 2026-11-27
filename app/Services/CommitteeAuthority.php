<?php

namespace App\Services;

use App\Enums\CommitteeRoleKind;
use App\Enums\CommitteeRoleScope;
use App\Enums\EventAbility;
use App\Models\CommitteeRole;
use App\Models\Division;
use App\Models\Event;
use App\Models\EventMembership;
use App\Models\User;

/**
 * Who may place whom in which committee role.
 *
 * - The organization (permission events.manage-committee) may assign any role.
 * - Whoever manages the committee of the event may assign every role except the chair
 *   and the supervisors: a chair is appointed by the organization, and supervisors
 *   oversee the committee, so the committee cannot pick them.
 * - A division manager may assign division members inside the divisions they manage.
 */
class CommitteeAuthority
{
    /**
     * @var list<CommitteeRoleKind>
     */
    private const ORGANIZATION_APPOINTED = [CommitteeRoleKind::Chair, CommitteeRoleKind::Supervisor];

    public function __construct(private EventAccess $access) {}

    public function canAssign(User $actor, Event $event, CommitteeRole $role, ?Division $division): bool
    {
        if (! $event->isEditable()) {
            return false;
        }

        if ($actor->can('events.manage-committee')) {
            return true;
        }

        if (in_array($role->kind, self::ORGANIZATION_APPOINTED, true)) {
            return false;
        }

        $permissions = $this->access->for($actor, $event);

        if ($permissions->has(EventAbility::CommitteeManage)) {
            return true;
        }

        return $role->scope === CommitteeRoleScope::Division
            && $division !== null
            && $role->kind !== CommitteeRoleKind::DivisionCoordinator
            && $permissions->has(EventAbility::DivisionManage, $division->getKey());
    }

    /**
     * Changing or ending a person's membership as a whole needs authority over every role they hold.
     */
    public function canManageMembership(User $actor, EventMembership $membership): bool
    {
        $event = $membership->event;

        if (! $event->isEditable()) {
            return false;
        }

        if ($actor->can('events.manage-committee') || $this->access->for($actor, $event)->has(EventAbility::CommitteeManage)) {
            return $membership->activeAssignments()->with('committeeRole')->get()
                ->every(fn ($assignment) => $this->canAssign($actor, $event, $assignment->committeeRole, $assignment->division));
        }

        $assignments = $membership->activeAssignments()->with(['committeeRole', 'division'])->get();

        return $assignments->isNotEmpty()
            && $assignments->every(fn ($assignment) => $this->canAssign($actor, $event, $assignment->committeeRole, $assignment->division));
    }
}
