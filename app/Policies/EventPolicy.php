<?php

namespace App\Policies;

use App\Enums\EventAbility;
use App\Models\Event;
use App\Models\User;
use App\Services\EventAccess;
use App\Services\LibraryAuthority;
use App\Services\MeetingAuthority;
use App\Services\TaskAuthority;

class EventPolicy
{
    public function __construct(
        private EventAccess $access,
        private TaskAuthority $work,
        private MeetingAuthority $meetings,
        private LibraryAuthority $library,
    ) {}

    public function create(User $user): bool
    {
        return $user->can('events.create');
    }

    /**
     * Anyone who holds an active role in the event, or may oversee every event.
     */
    public function view(User $user, Event $event): bool
    {
        return $this->access->for($user, $event)->hasAnywhere(EventAbility::EventView);
    }

    public function update(User $user, Event $event): bool
    {
        return $event->isEditable() && $this->access->for($user, $event)->has(EventAbility::EventUpdate);
    }

    /**
     * Moving between phases is part of running the event, so it follows the right to edit it.
     */
    public function changePhase(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    /**
     * Activating, completing, cancelling, reopening and archiving are organization decisions.
     */
    public function changeStatus(User $user, Event $event): bool
    {
        return $user->can('events.change-status');
    }

    public function viewCommittee(User $user, Event $event): bool
    {
        return $this->access->for($user, $event)->hasAnywhere(EventAbility::CommitteeView);
    }

    /**
     * Creating, renaming and removing divisions.
     */
    public function manageDivisions(User $user, Event $event): bool
    {
        return $event->isEditable() && $this->access->for($user, $event)->has(EventAbility::CommitteeManage);
    }

    /**
     * The "Program & Tugas" tab: work programs and tasks the person may see.
     */
    public function viewWork(User $user, Event $event): bool
    {
        return $this->work->canViewWork($user, $event);
    }

    /**
     * The "Rapat & Notulen" tab.
     */
    public function viewMeetings(User $user, Event $event): bool
    {
        return $this->meetings->canView($user, $event);
    }

    /**
     * The "Media & Dokumen" tab.
     */
    public function viewLibrary(User $user, Event $event): bool
    {
        return $this->library->canView($user, $event);
    }
}
