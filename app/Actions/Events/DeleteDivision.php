<?php

namespace App\Actions\Events;

use App\Models\Division;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteDivision
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * A division with sub-divisions, or that anyone was ever assigned to, keeps its history.
     *
     * @throws ValidationException
     */
    public function handle(Division $division, User $actor): void
    {
        if ($division->children()->exists()) {
            throw ValidationException::withMessages(['division' => 'Seksi ini masih memiliki sub-seksi. Pindahkan atau hapus sub-seksinya terlebih dahulu.']);
        }

        if ($division->assignments()->exists()) {
            throw ValidationException::withMessages(['division' => 'Seksi ini sudah pernah memiliki panitia sehingga tidak dapat dihapus, agar riwayat kepanitiaan tetap utuh.']);
        }

        DB::transaction(function () use ($division, $actor): void {
            $before = $division->getRawOriginal();
            $event = $division->event;

            $division->delete();

            $this->audit->record(
                action: 'divisions.deleted',
                auditable: $division,
                before: $before,
                eventId: $event->getKey(),
                organizationId: $event->organization_id,
                actor: $actor,
            );
        });
    }
}
