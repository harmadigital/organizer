<?php

namespace App\Actions\Meetings;

use App\Models\Meeting;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CommitteeNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteMeeting
{
    public function __construct(
        private AuditLogger $audit,
        private CommitteeNotifier $notifier,
    ) {}

    /**
     * A meeting whose minutes were written is part of the committee's record and stays.
     *
     * @throws ValidationException
     */
    public function handle(Meeting $meeting, User $actor): void
    {
        if ($meeting->hasMinutes()) {
            throw ValidationException::withMessages([
                'meeting' => 'Rapat yang sudah memiliki notulen tidak dapat dihapus.',
            ]);
        }

        DB::transaction(function () use ($meeting, $actor): void {
            $this->audit->record(
                action: 'meetings.deleted',
                auditable: $meeting,
                before: $meeting->only(['title', 'meets_on', 'starts_at', 'division_id']),
                eventId: $meeting->event_id,
                organizationId: $meeting->event->organization_id,
                actor: $actor,
            );

            $this->notifier->meetingCancelled($meeting, $actor);
            $meeting->delete();
        });
    }
}
