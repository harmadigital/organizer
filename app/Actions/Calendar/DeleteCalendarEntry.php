<?php

namespace App\Actions\Calendar;

use App\Models\CalendarEntry;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class DeleteCalendarEntry
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * A kegiatan that is no longer needed is deleted for good; the audit log keeps what it was.
     */
    public function handle(CalendarEntry $entry, User $actor): void
    {
        DB::transaction(function () use ($entry, $actor): void {
            $this->audit->record(
                action: 'calendar-entries.deleted',
                auditable: $entry,
                before: $entry->only(['title', 'division_id', 'starts_on', 'ends_on', 'starts_at', 'ends_at', 'user_id']),
                eventId: $entry->event_id,
                organizationId: $entry->event->organization_id,
                actor: $actor,
            );

            $entry->delete();
        });
    }
}
