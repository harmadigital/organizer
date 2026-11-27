<?php

namespace App\Actions\Meetings;

use App\Models\Event;
use App\Models\Meeting;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CommitteeNotifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveMeeting
{
    public function __construct(
        private AuditLogger $audit,
        private CommitteeNotifier $notifier,
    ) {}

    /**
     * Schedule or change a meeting. It is visible to the whole committee at once; the people it
     * is about are told when it is scheduled and when its day, time, place or division changes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Event $event, Meeting $meeting, array $attributes, User $actor): Meeting
    {
        return DB::transaction(function () use ($event, $meeting, $attributes, $actor): Meeting {
            $isNew = ! $meeting->exists;
            $scheduleBefore = $isNew ? null : $this->notifier->meetingSchedule($meeting);
            $meeting->event_id = $event->getKey();
            $meeting->fill($attributes);

            $changed = $meeting->getDirty();

            if (! $isNew && $changed === []) {
                return $meeting;
            }

            $before = $isNew ? [] : Arr::only($meeting->getRawOriginal(), array_keys($changed));
            $meeting->save();
            $meeting->setRelation('event', $event);

            $this->audit->record(
                action: 'meetings.'.($isNew ? 'created' : 'updated'),
                auditable: $meeting,
                before: $before,
                after: $changed,
                eventId: $event->getKey(),
                organizationId: $event->organization_id,
                actor: $actor,
            );

            if ($isNew) {
                $this->notifier->meetingScheduled($meeting, $actor);
            } elseif ($this->notifier->meetingSchedule($meeting) !== $scheduleBefore) {
                $this->notifier->meetingChanged($meeting, $actor);
            }

            return $meeting;
        });
    }
}
