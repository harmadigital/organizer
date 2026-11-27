<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Models\Event;
use App\Models\EventMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventMembership>
 */
class EventMembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'status' => MembershipStatus::Active,
            'invited_at' => now(),
            'responded_at' => now(),
        ];
    }

    public function invited(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MembershipStatus::Invited,
            'responded_at' => null,
        ]);
    }
}
