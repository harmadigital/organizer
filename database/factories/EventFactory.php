<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsOn = fake()->dateTimeBetween('+1 month', '+6 months');

        return [
            'organization_id' => Organization::factory(),
            'code' => 'EVT-'.fake()->unique()->numerify('####-###'),
            'name' => 'Event '.fake()->unique()->words(3, true),
            'theme' => fake()->sentence(4),
            'starts_on' => $startsOn,
            'ends_on' => (clone $startsOn)->modify('+1 day'),
        ];
    }

    /**
     * An event that passed the activation gate.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [])->afterMaking(function (Event $event): void {
            $event->status = EventStatus::Active;
            $event->activated_at = now();
        });
    }

    public function withStatus(EventStatus $status): static
    {
        return $this->afterMaking(function (Event $event) use ($status): void {
            $event->status = $status;
        });
    }
}
