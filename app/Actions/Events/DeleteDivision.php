<?php

namespace App\Actions\Events;

use App\Models\CalendarEntry;
use App\Models\Division;
use App\Models\Document;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkProgram;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteDivision
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * A division with sub-divisions, that anyone was ever assigned to, or that a meeting, task,
     * work program, document (archived ones included) or kegiatan still uses, keeps its history.
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

        if ($this->isStillUsed($division)) {
            throw ValidationException::withMessages(['division' => 'Seksi ini masih dipakai oleh rapat, tugas, program kerja, dokumen, atau kegiatan kalender. Pindahkan atau hapus data tersebut terlebih dahulu.']);
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

    /**
     * Every record that points at the division with a restrictive foreign key.
     */
    private function isStillUsed(Division $division): bool
    {
        return Meeting::query()->where('division_id', $division->getKey())->exists()
            || Task::query()->where('division_id', $division->getKey())->exists()
            || WorkProgram::query()->where('division_id', $division->getKey())->exists()
            || Document::withTrashed()->where('division_id', $division->getKey())->exists()
            || CalendarEntry::query()->where('division_id', $division->getKey())->exists();
    }
}
