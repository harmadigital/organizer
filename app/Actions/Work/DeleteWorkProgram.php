<?php

namespace App\Actions\Work;

use App\Models\User;
use App\Models\WorkProgram;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteWorkProgram
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * A program that already has tasks keeps its history; cancel it instead.
     */
    public function handle(WorkProgram $program, User $actor): void
    {
        if ($program->tasks()->exists()) {
            throw ValidationException::withMessages([
                'program' => 'Program kerja ini sudah memiliki tugas. Ubah statusnya menjadi Dibatalkan bila tidak dilanjutkan.',
            ]);
        }

        DB::transaction(function () use ($program, $actor): void {
            $this->audit->record(
                action: 'work-programs.deleted',
                auditable: $program,
                before: $program->only(['title', 'division_id', 'status']),
                eventId: $program->event_id,
                organizationId: $program->event->organization_id,
                actor: $actor,
            );

            $program->delete();
        });
    }
}
