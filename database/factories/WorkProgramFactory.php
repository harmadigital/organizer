<?php

namespace Database\Factories;

use App\Enums\WorkProgramStatus;
use App\Models\Division;
use App\Models\WorkProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkProgram>
 */
class WorkProgramFactory extends Factory
{
    /**
     * Define the model's default state. The event is taken from the division.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'division_id' => Division::factory(),
            'event_id' => fn (array $attributes) => Division::query()->whereKey($attributes['division_id'])->value('event_id'),
            'title' => fake()->sentence(3),
            'status' => WorkProgramStatus::Draft,
        ];
    }
}
