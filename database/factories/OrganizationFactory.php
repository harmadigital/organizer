<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'short_name' => fake()->lexify('????'),
            'timezone' => 'Asia/Jakarta',
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'phone' => fake()->numerify('021#######'),
            'email' => fake()->unique()->companyEmail(),
        ];
    }
}
