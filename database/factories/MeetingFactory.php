<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Meeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
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
            'title' => 'Rapat '.fake()->words(2, true),
            'meets_on' => '2026-12-01',
            'starts_at' => '19:00',
        ];
    }
}
