<?php

namespace Database\Factories;

use App\Enums\CommitteeRoleKind;
use App\Enums\CommitteeRoleScope;
use App\Models\CommitteeRole;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommitteeRole>
 */
class CommitteeRoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'key' => fake()->unique()->slug(2),
            'name' => fake()->unique()->jobTitle(),
            'kind' => CommitteeRoleKind::Custom,
            'scope' => CommitteeRoleScope::Event,
        ];
    }

    /**
     * Indicate that the record is no longer in use.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the role is one of the built-in committee positions.
     */
    public function ofKind(CommitteeRoleKind $kind): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $kind->value,
            'name' => $kind->label(),
            'kind' => $kind,
        ]);
    }

    /**
     * Indicate that the role only reaches the division it is assigned to.
     */
    public function divisionScoped(): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => CommitteeRoleScope::Division,
        ]);
    }
}
