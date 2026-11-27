<?php

namespace App\Actions\Events;

use App\Enums\EventPhase;
use App\Models\Event;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class ChangeEventPhase
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Event $event, EventPhase $target, ?string $note, User $actor): Event
    {
        return DB::transaction(function () use ($event, $target, $note, $actor): Event {
            $current = $event->phase;

            if ($current === $target) {
                return $event;
            }

            $event->phase = $target;
            $event->save();

            $event->statusHistories()->create([
                'from_status' => $event->status,
                'to_status' => $event->status,
                'from_phase' => $current,
                'to_phase' => $target,
                'reason' => $note,
                'actor_user_id' => $actor->getKey(),
            ]);

            $this->audit->record(
                action: 'events.phase_changed',
                auditable: $event,
                before: ['phase' => $current->value],
                after: ['phase' => $target->value],
                reason: $note,
                eventId: $event->getKey(),
                organizationId: $event->organization_id,
                actor: $actor,
            );

            return $event;
        });
    }
}
