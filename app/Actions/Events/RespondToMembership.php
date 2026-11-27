<?php

namespace App\Actions\Events;

use App\Enums\MembershipStatus;
use App\Models\EventMembership;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EventAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RespondToMembership
{
    public function __construct(
        private AuditLogger $audit,
        private EventAccess $access,
        private EndAssignment $endAssignment,
    ) {}

    /**
     * Accept or decline an invitation. When a manager confirms on the person's behalf,
     * the actor is the manager and the confirmation is recorded as theirs.
     *
     * @throws ValidationException
     */
    public function handle(EventMembership $membership, bool $accepts, User $actor): EventMembership
    {
        if ($membership->status !== MembershipStatus::Invited) {
            throw ValidationException::withMessages(['membership' => 'Undangan ini sudah ditanggapi.']);
        }

        return DB::transaction(function () use ($membership, $accepts, $actor): EventMembership {
            $onBehalf = $actor->getKey() !== $membership->user_id;

            if (! $accepts) {
                foreach ($membership->activeAssignments()->get() as $assignment) {
                    $this->endAssignment->handle($assignment, 'Menolak undangan kepanitiaan.', $actor);
                }
            }

            $membership->forceFill([
                'status' => $accepts ? MembershipStatus::Active : MembershipStatus::Declined,
                'responded_at' => now(),
                'confirmed_by' => $onBehalf ? $actor->getKey() : null,
            ])->save();
            $this->access->forget();

            $this->audit->record(
                action: $accepts ? 'committee.membership_accepted' : 'committee.membership_declined',
                auditable: $membership,
                before: ['status' => MembershipStatus::Invited->value],
                after: ['status' => $membership->status->value, 'on_behalf' => $onBehalf],
                eventId: $membership->event_id,
                organizationId: $membership->event->organization_id,
                actor: $actor,
            );

            return $membership;
        });
    }
}
