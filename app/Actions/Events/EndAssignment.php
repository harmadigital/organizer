<?php

namespace App\Actions\Events;

use App\Models\EventRoleAssignment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EventAccess;
use Illuminate\Support\Facades\DB;

class EndAssignment
{
    public function __construct(
        private AuditLogger $audit,
        private EventAccess $access,
    ) {}

    /**
     * End one role. The record stays as part of the committee's history.
     */
    public function handle(EventRoleAssignment $assignment, string $reason, User $actor): void
    {
        if (! $assignment->isActive()) {
            return;
        }

        DB::transaction(function () use ($assignment, $reason, $actor): void {
            $assignment->forceFill([
                'ends_at' => now(),
                'ended_reason' => $reason,
                'ended_by' => $actor->getKey(),
            ]);
            $assignment->refreshUniquenessKeys(false);
            $assignment->save();
            $this->access->forget();

            $this->audit->record(
                action: 'committee.assignment_ended',
                auditable: $assignment,
                before: ['committee_role_id' => $assignment->committee_role_id, 'division_id' => $assignment->division_id],
                reason: $reason,
                eventId: $assignment->event_id,
                organizationId: $assignment->event->organization_id,
                actor: $actor,
            );
        });
    }
}
