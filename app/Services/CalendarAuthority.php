<?php

namespace App\Services;

use App\Enums\EventAbility;
use App\Models\CalendarEntry;
use App\Models\Event;
use App\Models\User;

/**
 * Who may see the event calendar and add or change its kegiatan.
 *
 * - Every active committee member sees the calendar, and so does the organization through
 *   EventView. Meetings and task deadlines on it still follow their own rules
 *   (MeetingAuthority, TaskAuthority).
 * - Every active committee member may add a kegiatan, for any division or for the core team.
 *   The organization adds through MeetingManage (events.manage-work).
 * - A kegiatan is changed or deleted by whoever added it while they are still an active
 *   member, or by MeetingManage for its division (event-wide for core team kegiatan: by
 *   default the Sekretaris; a division coordinator for their division and those below it).
 * - Nothing changes once the event is completed, cancelled or archived.
 */
class CalendarAuthority
{
    public function __construct(private EventAccess $access) {}

    public function canView(User $user, Event $event): bool
    {
        $permissions = $this->access->for($user, $event);

        return $permissions->isActiveMember() || $permissions->hasAnywhere(EventAbility::EventView);
    }

    public function canAdd(User $user, Event $event): bool
    {
        $permissions = $this->access->for($user, $event);

        return $event->isEditable() && ($permissions->isActiveMember() || $permissions->hasAnywhere(EventAbility::MeetingManage));
    }

    public function canChange(User $user, CalendarEntry $entry): bool
    {
        $event = $entry->event;

        if (! $event->isEditable()) {
            return false;
        }

        $permissions = $this->access->for($user, $event);

        return ($entry->user_id === $user->getKey() && $permissions->isActiveMember())
            || $permissions->has(EventAbility::MeetingManage, $entry->division_id);
    }

    /**
     * Whether the person may manage kegiatan of this division (event-wide for the core team).
     */
    public function canManageIn(User $user, Event $event, ?int $divisionId): bool
    {
        return $event->isEditable() && $this->access->for($user, $event)->has(EventAbility::MeetingManage, $divisionId);
    }

    /**
     * The division someone adding a kegiatan most likely belongs to, used as the form default.
     */
    public function defaultDivisionId(User $user, Event $event): ?int
    {
        $membership = $this->access->for($user, $event)->membership;

        return $membership?->activeAssignments()->whereNotNull('division_id')->orderBy('id')->value('division_id');
    }
}
