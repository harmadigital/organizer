<?php

namespace Database\Factories;

use App\Models\CommitteeRole;
use App\Models\EventMembership;
use App\Models\EventRoleAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Prefer AssignCommitteeRole in tests about committee rules; this factory skips them.
 *
 * @extends Factory<EventRoleAssignment>
 */
class EventRoleAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_membership_id' => EventMembership::factory(),
            'event_id' => fn (array $attributes) => EventMembership::query()->find($attributes['event_membership_id'])->event_id,
            'committee_role_id' => CommitteeRole::factory(),
            'is_primary' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (EventRoleAssignment $assignment): void {
            $assignment->refreshUniquenessKeys(false);
        });
    }
}
