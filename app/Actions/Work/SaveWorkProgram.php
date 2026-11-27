<?php

namespace App\Actions\Work;

use App\Models\Event;
use App\Models\User;
use App\Models\WorkProgram;
use App\Services\AuditLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveWorkProgram
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Event $event, WorkProgram $program, array $attributes, User $actor): WorkProgram
    {
        return DB::transaction(function () use ($event, $program, $attributes, $actor): WorkProgram {
            $isNew = ! $program->exists;
            $program->event_id = $event->getKey();
            $program->fill($attributes);

            $changed = $program->getDirty();

            if (! $isNew && $changed === []) {
                return $program;
            }

            $before = $isNew ? [] : Arr::only($program->getRawOriginal(), array_keys($changed));
            $program->save();

            $this->audit->record(
                action: 'work-programs.'.($isNew ? 'created' : 'updated'),
                auditable: $program,
                before: $before,
                after: $changed,
                eventId: $event->getKey(),
                organizationId: $event->organization_id,
                actor: $actor,
            );

            return $program;
        });
    }
}
