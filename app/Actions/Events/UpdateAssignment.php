<?php

namespace App\Actions\Events;

use App\Models\EventRoleAssignment;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAssignment
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Change the job description, or whether the person is the primary holder of the role.
     *
     * @throws ValidationException
     */
    public function handle(EventRoleAssignment $assignment, ?string $jobDescription, bool $isPrimary, User $actor): EventRoleAssignment
    {
        return DB::transaction(function () use ($assignment, $jobDescription, $isPrimary, $actor): EventRoleAssignment {
            $rule = $assignment->event->roleRules()->where('committee_role_id', $assignment->committee_role_id)->first();
            $isSingleton = (bool) $rule?->is_singleton;

            $assignment->job_description = $jobDescription;
            $assignment->is_primary = $isSingleton ? $isPrimary : true;

            if ($isSingleton && $assignment->is_primary && $assignment->isDirty('is_primary')) {
                $primaryTaken = EventRoleAssignment::query()
                    ->active()
                    ->whereKeyNot($assignment->getKey())
                    ->where('event_id', $assignment->event_id)
                    ->where('committee_role_id', $assignment->committee_role_id)
                    ->where('division_id', $assignment->division_id)
                    ->where('is_primary', true)
                    ->exists();

                if ($primaryTaken) {
                    throw ValidationException::withMessages(['is_primary' => 'Jabatan ini sudah memiliki pemegang utama.']);
                }
            }

            $assignment->refreshUniquenessKeys($isSingleton);
            $changed = array_intersect_key($assignment->getDirty(), array_flip(['job_description', 'is_primary']));

            if ($changed === []) {
                return $assignment;
            }

            $before = array_intersect_key($assignment->getRawOriginal(), $changed);
            $assignment->save();

            $this->audit->record(
                action: 'committee.assignment_updated',
                auditable: $assignment,
                before: $before,
                after: $changed,
                eventId: $assignment->event_id,
                organizationId: $assignment->event->organization_id,
                actor: $actor,
            );

            return $assignment;
        });
    }
}
