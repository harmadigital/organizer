<?php

namespace App\Actions\Events;

use App\Models\Division;
use App\Models\Event;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EventAccess;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveDivision
{
    public function __construct(
        private AuditLogger $audit,
        private EventAccess $access,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Event $event, Division $division, array $attributes, User $actor): Division
    {
        return DB::transaction(function () use ($event, $division, $attributes, $actor): Division {
            $isNew = ! $division->exists;
            $division->event_id = $event->getKey();
            $division->fill($attributes);

            $changed = $division->getDirty();

            if (! $isNew && $changed === []) {
                return $division;
            }

            $before = $isNew ? [] : Arr::only($division->getRawOriginal(), array_keys($changed));
            $division->save();
            $this->access->forget();

            $this->audit->record(
                action: 'divisions.'.($isNew ? 'created' : 'updated'),
                auditable: $division,
                before: $before,
                after: $changed,
                eventId: $event->getKey(),
                organizationId: $event->organization_id,
                actor: $actor,
            );

            return $division;
        });
    }
}
