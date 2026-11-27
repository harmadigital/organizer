<?php

namespace Tests\Concerns;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\PermissionSeeder;

trait InteractsWithOrganization
{
    protected Organization $organization;

    /**
     * Seed the permission catalogue and create the organization every request works for.
     */
    protected function setUpOrganization(): Organization
    {
        $this->seed(PermissionSeeder::class);

        return $this->organization = Organization::factory()->create();
    }

    /**
     * A signed-in-able user holding exactly the given organization-level permissions.
     */
    protected function userWithPermissions(string ...$permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }
}
