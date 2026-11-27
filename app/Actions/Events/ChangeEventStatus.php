<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EventReadiness;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeEventStatus
{
    public function __construct(
        private AuditLogger $audit,
        private EventReadiness $readiness,
    ) {}

    /**
     * Move an event to another status, enforcing the allowed transitions and the
     * activation gate: an event cannot become active while a required role is empty.
     *
     * @throws ValidationException
     */
    public function handle(Event $event, EventStatus $target, ?string $reason, User $actor): Event
    {
        return DB::transaction(function () use ($event, $target, $reason, $actor): Event {
            $event = Event::query()->whereKey($event->getKey())->lockForUpdate()->firstOrFail();
            $current = $event->status;

            if (! $current->canMoveTo($target)) {
                throw ValidationException::withMessages([
                    'status' => "Event berstatus {$current->label()} tidak dapat diubah menjadi {$target->label()}.",
                ]);
            }

            if ($current->requiresReasonToMoveTo($target) && blank($reason)) {
                throw ValidationException::withMessages(['reason' => 'Alasan wajib diisi untuk perubahan status ini.']);
            }

            if ($target === EventStatus::Active && $current === EventStatus::Draft) {
                $this->guardActivation($event);
                $event->activated_at = now();
            }

            if (in_array($target, [EventStatus::Completed, EventStatus::Cancelled], true)) {
                $event->closed_at = now();
            }

            if (in_array($target, [EventStatus::Active, EventStatus::Draft], true)) {
                $event->closed_at = null;
            }

            $event->status = $target;
            $event->save();

            $event->statusHistories()->create([
                'from_status' => $current,
                'to_status' => $target,
                'from_phase' => $event->phase,
                'to_phase' => $event->phase,
                'reason' => $reason,
                'actor_user_id' => $actor->getKey(),
            ]);

            $this->audit->record(
                action: 'events.status_changed',
                auditable: $event,
                before: ['status' => $current->value],
                after: ['status' => $target->value],
                reason: $reason,
                eventId: $event->getKey(),
                organizationId: $event->organization_id,
                actor: $actor,
            );

            return $event;
        });
    }

    /**
     * @throws ValidationException
     */
    private function guardActivation(Event $event): void
    {
        $missing = $this->readiness->missingRequiredRoles($event);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'status' => 'Event belum dapat diaktifkan. Jabatan wajib yang belum terisi oleh panitia yang sudah bersedia: '.implode(', ', $missing).'.',
            ]);
        }
    }
}
