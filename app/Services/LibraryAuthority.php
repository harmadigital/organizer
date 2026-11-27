<?php

namespace App\Services;

use App\Enums\EventAbility;
use App\Models\Document;
use App\Models\Event;
use App\Models\User;

/**
 * Who may use the event's shared media and document library.
 *
 * - Every active committee member sees every item and may add photos, documents and links;
 *   so does the organization through DocumentView (seeing) and DocumentManage (adding).
 * - An item is removed by whoever added it, or by DocumentManage for its division (event-wide
 *   for items without a division).
 * - Nothing changes once the event is completed, cancelled or archived.
 */
class LibraryAuthority
{
    public function __construct(private EventAccess $access) {}

    public function canView(User $user, Event $event): bool
    {
        $permissions = $this->access->for($user, $event);

        return $permissions->isActiveMember() || $permissions->hasAnywhere(EventAbility::DocumentView);
    }

    public function canAdd(User $user, Event $event): bool
    {
        $permissions = $this->access->for($user, $event);

        return $event->isEditable() && ($permissions->isActiveMember() || $permissions->hasAnywhere(EventAbility::DocumentManage));
    }

    public function canRemove(User $user, Document $document): bool
    {
        $event = $document->event;

        return $event->isEditable()
            && ($document->owner_user_id === $user->getKey() || $this->access->for($user, $event)->has(EventAbility::DocumentManage, $document->division_id));
    }

    /**
     * The division someone adding an item most likely belongs to, used as the form default.
     */
    public function defaultDivisionId(User $user, Event $event): ?int
    {
        $membership = $this->access->for($user, $event)->membership;

        return $membership?->activeAssignments()->whereNotNull('division_id')->orderBy('id')->value('division_id');
    }
}
