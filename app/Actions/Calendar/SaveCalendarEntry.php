<?php

namespace App\Actions\Calendar;

use App\Models\CalendarEntry;
use App\Models\Event;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveCalendarEntry
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Add or change a kegiatan on the event calendar. The attributes are the whole form: an
     * empty end date means a one-day kegiatan, and without a start time it lasts all day. A
     * kegiatan of several days always lasts all day, also when a change leaves the times out.
     * Whoever adds a kegiatan becomes its author.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Event $event, CalendarEntry $entry, array $attributes, User $actor): CalendarEntry
    {
        return DB::transaction(function () use ($event, $entry, $attributes, $actor): CalendarEntry {
            $isNew = ! $entry->exists;

            $entry->event_id = $event->getKey();

            if ($isNew) {
                $entry->user_id = $actor->getKey();
            }

            $entry->fill($this->normalize($attributes));

            if ($entry->lastsSeveralDays()) {
                $entry->starts_at = null;
                $entry->ends_at = null;
            }

            $changed = $entry->getDirty();

            if (! $isNew && $changed === []) {
                return $entry;
            }

            $before = $isNew ? [] : Arr::only($entry->getRawOriginal(), array_keys($changed));
            $entry->save();

            $this->audit->record(
                action: 'calendar-entries.'.($isNew ? 'created' : 'updated'),
                auditable: $entry,
                before: $before,
                after: $changed,
                eventId: $event->getKey(),
                organizationId: $event->organization_id,
                actor: $actor,
            );

            return $entry;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalize(array $attributes): array
    {
        if (filled($attributes['starts_on'] ?? null) && blank($attributes['ends_on'] ?? null)) {
            $attributes['ends_on'] = $attributes['starts_on'];
        }

        if (array_key_exists('starts_at', $attributes) && blank($attributes['starts_at'])) {
            $attributes['starts_at'] = null;
            $attributes['ends_at'] = null;
        }

        foreach (['starts_at', 'ends_at'] as $time) {
            // Stored as H:i:s, the way MySQL returns a time, so an unchanged form changes nothing.
            if (filled($attributes[$time] ?? null) && strlen($attributes[$time]) === 5) {
                $attributes[$time] .= ':00';
            }
        }

        return $attributes;
    }
}
