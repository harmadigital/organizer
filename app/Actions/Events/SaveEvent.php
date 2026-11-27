<?php

namespace App\Actions\Events;

use App\Enums\EventPhase;
use App\Enums\EventStatus;
use App\Models\CommitteeRole;
use App\Models\Event;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CurrentOrganization;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveEvent
{
    public function __construct(
        private AuditLogger $audit,
        private CurrentOrganization $organization,
    ) {}

    /**
     * Create or update an event with its goals, targets and success indicators.
     *
     * A new event starts as a draft and takes its own copy of the organization's
     * committee role rules, so later changes to those defaults do not touch it.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<array{type: string, description: string, target: ?string}>  $objectives
     */
    public function handle(Event $event, array $attributes, array $objectives, User $actor): Event
    {
        return DB::transaction(function () use ($event, $attributes, $objectives, $actor): Event {
            $isNew = ! $event->exists;
            $organization = $this->organization->get();

            if ($isNew) {
                $event->organization_id = $organization->getKey();
                $event->currency_code = $organization->currency_code;
            }

            $event->fill($attributes);
            $event->code = filled($attributes['code'] ?? null) ? $attributes['code'] : ($event->code ?? $this->nextCode($event));

            $changed = $event->getDirty();
            $before = $isNew ? [] : Arr::only($event->getRawOriginal(), array_keys($changed));
            $objectivesBefore = $isNew ? [] : $this->objectivesOf($event);

            $event->save();
            $this->syncObjectives($event, $objectives);
            $objectivesAfter = $this->objectivesOf($event);

            if ($isNew) {
                $this->copyRoleRules($event);
                $event->statusHistories()->create([
                    'to_status' => EventStatus::Draft,
                    'to_phase' => EventPhase::Initiation,
                    'reason' => 'Event dibuat.',
                    'actor_user_id' => $actor->getKey(),
                ]);
            }

            if ($isNew || $changed !== [] || $objectivesBefore !== $objectivesAfter) {
                $this->audit->record(
                    action: 'events.'.($isNew ? 'created' : 'updated'),
                    auditable: $event,
                    before: $isNew ? [] : [...$before, 'objectives' => $objectivesBefore],
                    after: [...$changed, 'objectives' => $objectivesAfter],
                    eventId: $event->getKey(),
                    organizationId: $event->organization_id,
                    actor: $actor,
                );
            }

            return $event;
        });
    }

    /**
     * @param  list<array{type: string, description: string, target: ?string}>  $objectives
     */
    private function syncObjectives(Event $event, array $objectives): void
    {
        $event->objectives()->delete();

        foreach (array_values($objectives) as $position => $objective) {
            $event->objectives()->create([
                'type' => $objective['type'],
                'description' => $objective['description'],
                'target' => $objective['target'] ?? null,
                'sort_order' => $position,
            ]);
        }
    }

    /**
     * @return list<array{type: string, description: string, target: ?string}>
     */
    private function objectivesOf(Event $event): array
    {
        return $event->objectives()->get()
            ->map(fn ($objective): array => [
                'type' => $objective->type->value,
                'description' => $objective->description,
                'target' => $objective->target,
            ])
            ->all();
    }

    private function copyRoleRules(Event $event): void
    {
        CommitteeRole::query()
            ->forOrganization($event->organization_id)
            ->active()
            ->get()
            ->each(fn (CommitteeRole $role) => $event->roleRules()->create([
                'committee_role_id' => $role->getKey(),
                'is_required' => $role->is_required_default,
                'is_singleton' => $role->is_singleton_default,
            ]));
    }

    /**
     * EVT-<year of the event>-<three digit sequence within that year>.
     */
    private function nextCode(Event $event): string
    {
        $year = $event->starts_on?->format('Y') ?? now()->format('Y');
        $prefix = "EVT-{$year}-";

        $usedCodes = Event::query()
            ->forOrganization($event->organization_id)
            ->where('code', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('code');

        $sequence = $usedCodes
            ->map(fn (string $code): int => (int) substr($code, strlen($prefix)))
            ->max() ?? 0;

        do {
            $code = $prefix.str_pad((string) ++$sequence, 3, '0', STR_PAD_LEFT);
        } while ($usedCodes->contains($code));

        return $code;
    }
}
