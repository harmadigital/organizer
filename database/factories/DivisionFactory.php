<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Division>
 */
class DivisionFactory extends Factory
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
            'name' => 'Seksi '.fake()->unique()->word(),
            'description' => fake()->sentence(),
            'sort_order' => 0,
        ];
    }
}
