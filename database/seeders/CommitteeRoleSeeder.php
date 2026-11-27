<?php

namespace Database\Seeders;

use App\Actions\Organization\ProvisionDefaultCommitteeRoles;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class CommitteeRoleSeeder extends Seeder
{
    /**
     * Give every existing organization the built-in committee roles it is missing.
     * Roles an administrator already changed are left as they are.
     */
    public function run(ProvisionDefaultCommitteeRoles $provisionRoles): void
    {
        Organization::query()->each(fn (Organization $organization) => $provisionRoles->handle($organization));
    }
}
