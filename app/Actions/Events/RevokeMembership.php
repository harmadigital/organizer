<?php

namespace App\Actions\Events;

use App\Enums\MembershipStatus;
use App\Models\EventMembership;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EventAccess;
use Illuminate\Support\Facades\DB;

class RevokeMembership
{
    public function __construct(
        private AuditLogger $audit,
        private EventAccess $access,
        private EndAssignment $endAssignment,
    ) {}

    /**
     * Remove a person from the committee. Every role they hold ends and their access
     * to the event stops at once.
     */
    public function handle(EventMembership $membership, string $reason, User $actor): void
    {
        DB::transaction(function () use ($membership, $reason, $actor): void {
            foreach ($membership->activeAssignments()->get() as $assignment) {
                $this->endAssignment->handle($assignment, $reason, $actor);
            }

            $before = $membership->status;

            $membership->forceFill([
                'status' => MembershipStatus::Revoked,
                'revoked_at' => now(),
                'revoked_by' => $actor->getKey(),
                'revoked_reason' => $reason,
            ])->save();
            $this->access->forget();

            $this->audit->record(
                action: 'committee.membership_revoked',
                auditable: $membership,
                before: ['status' => $before->value],
                after: ['status' => MembershipStatus::Revoked->value],
                reason: $reason,
                eventId: $membership->event_id,
                organizationId: $membership->event->organization_id,
                actor: $actor,
            );
        });
    }
}
