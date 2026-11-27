<?php

namespace App\Actions\MasterData;

use App\Exceptions\RecordInUseException;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class DeleteMasterRecord
{
    private const INTEGRITY_CONSTRAINT_VIOLATION = '23000';

    public function __construct(private AuditLogger $audit) {}

    /**
     * Delete a master data record that nothing refers to.
     *
     * The foreign keys decide what "referred to" means: every table that points
     * at master data restricts deletion, so this stays correct as modules are added.
     *
     * @param  string  $subject  Audit prefix, e.g. "venues" gives "venues.deleted".
     *
     * @throws RecordInUseException
     */
    public function handle(Model $record, string $subject): void
    {
        try {
            DB::transaction(function () use ($record, $subject): void {
                $before = $record->getRawOriginal();

                $record->delete();

                $this->audit->record(
                    action: $subject.'.deleted',
                    auditable: $record,
                    before: $before,
                    organizationId: $record->getAttribute('organization_id'),
                );
            });
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? null) === self::INTEGRITY_CONSTRAINT_VIOLATION) {
                throw new RecordInUseException;
            }

            throw $exception;
        }
    }
}
