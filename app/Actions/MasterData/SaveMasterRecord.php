<?php

namespace App\Actions\MasterData;

use App\Models\Organization;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveMasterRecord
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Create or update a master data record and audit what changed.
     *
     * @template TModel of Model
     *
     * @param  TModel  $record
     * @param  array<string, mixed>  $attributes
     * @param  string  $subject  Audit prefix, e.g. "venues" gives "venues.created" and "venues.updated".
     * @return TModel
     */
    public function handle(Model $record, array $attributes, string $subject): Model
    {
        return DB::transaction(function () use ($record, $attributes, $subject): Model {
            $isNew = ! $record->exists;
            $record->fill($attributes);

            $after = $record->getDirty();

            if ($after === []) {
                return $record;
            }

            $before = $isNew ? [] : Arr::only($record->getRawOriginal(), array_keys($after));

            $record->save();

            $this->audit->record(
                action: $subject.($isNew ? '.created' : '.updated'),
                auditable: $record,
                before: $before,
                after: $after,
                organizationId: $record instanceof Organization ? $record->getKey() : $record->getAttribute('organization_id'),
            );

            return $record;
        });
    }
}
