<?php

namespace Database\Factories;

use App\Models\CalendarEntry;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEntry>
 */
class CalendarEntryFactory extends Factory
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
            'title' => 'Kegiatan '.fake()->words(2, true),
            'starts_on' => '2026-12-05',
            'ends_on' => '2026-12-05',
        ];
    }
}
