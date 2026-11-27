<?php

namespace App\Services;

use App\Enums\EventAbility;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\MeetingComment;
use App\Models\User;

/**
 * Who may see and run the meetings of an event.
 *
 * - Every active committee member sees every meeting and its minutes; so does the
 *   organization through MeetingView. There are no invitations.
 * - Scheduling, changing a meeting and writing its minutes needs MeetingManage: event-wide
 *   for core team meetings (no division, by default the Sekretaris), or for the division.
 * - Every active committee member may comment.
 * - Nothing changes once the event is completed, cancelled or archived.
 */
class MeetingAuthority
{
    public function __construct(private EventAccess $access) {}

    public function canView(User $user, Event $event): bool
    {
        $permissions = $this->access->for($user, $event);

        return $permissions->isActiveMember() || $permissions->hasAnywhere(EventAbility::MeetingView);
    }

    public function canSchedule(User $user, Event $event): bool
    {
        return $event->isEditable() && $this->access->for($user, $event)->hasAnywhere(EventAbility::MeetingManage);
    }

    public function canManageIn(User $user, Event $event, ?int $divisionId): bool
    {
        return $event->isEditable() && $this->access->for($user, $event)->has(EventAbility::MeetingManage, $divisionId);
    }

    public function canManage(User $user, Meeting $meeting): bool
    {
        return $this->canManageIn($user, $meeting->event, $meeting->division_id);
    }

    public function canComment(User $user, Meeting $meeting): bool
    {
        return $meeting->event->isEditable()
            && ($this->access->for($user, $meeting->event)->isActiveMember() || $this->canManage($user, $meeting));
    }

    public function canDeleteComment(User $user, MeetingComment $comment): bool
    {
        return $comment->meeting->event->isEditable()
            && ($comment->user_id === $user->getKey() || $this->canManage($user, $comment->meeting));
    }
}
