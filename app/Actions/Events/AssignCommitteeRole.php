<?php

namespace App\Actions\Events;

use App\Enums\CommitteeRoleKind;
use App\Enums\CommitteeRoleScope;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\CommitteeRole;
use App\Models\Division;
use App\Models\Event;
use App\Models\EventMembership;
use App\Models\EventRoleAssignment;
use App\Models\EventRoleRule;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CommitteeNotifier;
use App\Services\EventAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignCommitteeRole
{
    public function __construct(
        private AuditLogger $audit,
        private EventAccess $access,
        private CommitteeNotifier $notifier,
    ) {}

    /**
     * Give a person a committee role in an event, adding them to the committee when needed.
     *
     * A new member is invited and gains access once they accept, unless the person
     * assigning confirms on their behalf that they already agreed (needed for people
     * without login access). Someone who declined or was removed earlier is invited again.
     *
     * @throws ValidationException
     */
    public function handle(
        Event $event,
        User $person,
        CommitteeRole $role,
        ?Division $division,
        bool $isPrimary,
        ?string $jobDescription,
        bool $confirmNow,
        User $actor,
    ): EventRoleAssignment {
        return DB::transaction(function () use ($event, $person, $role, $division, $isPrimary, $jobDescription, $confirmNow, $actor): EventRoleAssignment {
            Event::query()->whereKey($event->getKey())->lockForUpdate()->first();

            $this->guardPerson($person);
            $this->guardRoleAndDivision($event, $role, $division);

            $rule = $this->ruleFor($event, $role);
            $membership = $this->membershipFor($event, $person, $confirmNow, $actor);

            $this->guardSeparationOfDuties($membership, $role);
            $this->guardDuplicate($membership, $role, $division);

            $assignment = new EventRoleAssignment([
                'event_id' => $event->getKey(),
                'event_membership_id' => $membership->getKey(),
                'committee_role_id' => $role->getKey(),
                'division_id' => $division?->getKey(),
                'is_primary' => $rule->is_singleton ? $isPrimary : true,
                'job_description' => $jobDescription,
                'assigned_by' => $actor->getKey(),
            ]);

            $this->guardSinglePrimary($assignment, $rule, $role);
            $assignment->refreshUniquenessKeys($rule->is_singleton);
            $assignment->save();
            $this->access->forget();

            $this->audit->record(
                action: 'committee.assigned',
                auditable: $assignment,
                after: [
                    'user_id' => $person->getKey(),
                    'committee_role' => $role->name,
                    'division' => $division?->name,
                    'is_primary' => $assignment->is_primary,
                    'membership_status' => $membership->status->value,
                ],
                eventId: $event->getKey(),
                organizationId: $event->organization_id,
                actor: $actor,
            );

            $this->notifier->invited($membership->setRelation('event', $event)->setRelation('user', $person), $role, $division, $actor);

            return $assignment;
        });
    }

    /**
     * @throws ValidationException
     */
    private function guardPerson(User $person): void
    {
        if ($person->status !== UserStatus::Active) {
            throw ValidationException::withMessages(['user_id' => 'User nonaktif tidak dapat ditugaskan dalam kepanitiaan.']);
        }
    }

    /**
     * @throws ValidationException
     */
    private function guardRoleAndDivision(Event $event, CommitteeRole $role, ?Division $division): void
    {
        if (! $role->is_active || $role->organization_id !== $event->organization_id) {
            throw ValidationException::withMessages(['committee_role_id' => 'Jabatan ini tidak tersedia.']);
        }

        if ($role->scope === CommitteeRoleScope::Division && $division === null) {
            throw ValidationException::withMessages(['division_id' => "Jabatan {$role->name} harus dikaitkan dengan seksi."]);
        }

        if ($role->scope === CommitteeRoleScope::Event && $division !== null) {
            throw ValidationException::withMessages(['division_id' => "Jabatan {$role->name} berlaku untuk seluruh event, bukan untuk satu seksi."]);
        }

        if ($division !== null && $division->event_id !== $event->getKey()) {
            throw ValidationException::withMessages(['division_id' => 'Seksi tidak ditemukan pada event ini.']);
        }
    }

    /**
     * Roles added to the organization after the event was created get their rules on first use.
     */
    private function ruleFor(Event $event, CommitteeRole $role): EventRoleRule
    {
        return $event->roleRules()->firstOrCreate(
            ['committee_role_id' => $role->getKey()],
            ['is_required' => false, 'is_singleton' => $role->is_singleton_default],
        );
    }

    private function membershipFor(Event $event, User $person, bool $confirmNow, User $actor): EventMembership
    {
        $membership = EventMembership::query()
            ->where('event_id', $event->getKey())
            ->where('user_id', $person->getKey())
            ->lockForUpdate()
            ->first() ?? new EventMembership(['event_id' => $event->getKey(), 'user_id' => $person->getKey()]);

        $needsInvitation = ! $membership->exists
            || in_array($membership->status, [MembershipStatus::Declined, MembershipStatus::Revoked], true);

        if ($needsInvitation) {
            $membership->fill([
                'status' => MembershipStatus::Invited,
                'invited_by' => $actor->getKey(),
                'invited_at' => now(),
                'responded_at' => null,
                'confirmed_by' => null,
                'revoked_at' => null,
                'revoked_by' => null,
                'revoked_reason' => null,
            ]);
        }

        if ($confirmNow && $membership->status === MembershipStatus::Invited) {
            $membership->fill([
                'status' => MembershipStatus::Active,
                'responded_at' => now(),
                'confirmed_by' => $actor->getKey(),
            ]);
        }

        $membership->save();

        return $membership;
    }

    /**
     * Supervisors oversee the committee, so nobody may be both a supervisor and a member
     * of the committee they supervise.
     *
     * @throws ValidationException
     */
    private function guardSeparationOfDuties(EventMembership $membership, CommitteeRole $role): void
    {
        $heldKinds = $membership->activeAssignments()->with('committeeRole')->get()
            ->map(fn (EventRoleAssignment $assignment) => $assignment->committeeRole->kind);

        if ($heldKinds->isEmpty()) {
            return;
        }

        $isSupervisor = $role->kind === CommitteeRoleKind::Supervisor;
        $holdsSupervisor = $heldKinds->contains(CommitteeRoleKind::Supervisor);
        $holdsOther = $heldKinds->contains(fn (CommitteeRoleKind $kind): bool => $kind !== CommitteeRoleKind::Supervisor);

        if ($isSupervisor && $holdsOther) {
            throw ValidationException::withMessages(['committee_role_id' => 'Orang ini sudah menjadi pelaksana dalam panitia sehingga tidak dapat sekaligus menjadi pengawas.']);
        }

        if (! $isSupervisor && $holdsSupervisor) {
            throw ValidationException::withMessages(['committee_role_id' => 'Orang ini adalah pengawas event sehingga tidak dapat sekaligus menjadi pelaksana.']);
        }
    }

    /**
     * @throws ValidationException
     */
    private function guardDuplicate(EventMembership $membership, CommitteeRole $role, ?Division $division): void
    {
        $alreadyHolds = $membership->activeAssignments()
            ->where('committee_role_id', $role->getKey())
            ->where('division_id', $division?->getKey())
            ->exists();

        if ($alreadyHolds) {
            throw ValidationException::withMessages(['committee_role_id' => 'Orang ini sudah memegang jabatan tersebut.']);
        }
    }

    /**
     * @throws ValidationException
     */
    private function guardSinglePrimary(EventRoleAssignment $assignment, EventRoleRule $rule, CommitteeRole $role): void
    {
        if (! $rule->is_singleton || ! $assignment->is_primary) {
            return;
        }

        $primaryTaken = EventRoleAssignment::query()
            ->active()
            ->where('event_id', $assignment->event_id)
            ->where('committee_role_id', $role->getKey())
            ->where('division_id', $assignment->division_id)
            ->where('is_primary', true)
            ->exists();

        if ($primaryTaken) {
            throw ValidationException::withMessages([
                'is_primary' => "Jabatan {$role->name} sudah memiliki pemegang utama. Tugaskan orang ini sebagai pendamping, atau akhiri jabatan pemegang utama terlebih dahulu.",
            ]);
        }
    }
}
