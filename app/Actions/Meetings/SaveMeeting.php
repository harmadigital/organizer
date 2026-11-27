<?php

namespace App\Actions\Meetings;

use App\Models\Event;
use App\Models\Meeting;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveMeeting
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Schedule or change a meeting. It is visible to the whole committee at once.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Event $event, Meeting $meeting, array $attributes, User $actor): Meeting
    {
        return DB::transaction(function () use ($event, $meeting, $attributes, $actor): Meeting {
            $isNew = ! $meeting->exists;
            $meeting->event_id = $event->getKey();
            $meeting->fill($attributes);

            $changed = $meeting->getDirty();

            if (! $isNew && $changed === []) {
                return $meeting;
            }

            $before = $isNew ? [] : Arr::only($meeting->getRawOriginal(), array_keys($changed));
            $meeting->save();

            $this->audit->record(
                action: 'meetings.'.($isNew ? 'created' : 'updated'),
                auditable: $meeting,
                before: $before,
                after: $changed,
                eventId: $event->getKey(),
                organizationId: $event->organization_id,
                actor: $actor,
            );

            return $meeting;
        });
    }
}
