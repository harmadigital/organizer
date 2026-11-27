<?php

namespace App\Actions\Organization;

use App\Actions\MasterData\SaveMasterRecord;
use App\Models\Organization;
use App\Services\CurrentOrganization;
use Illuminate\Support\Facades\DB;

class SaveOrganizationProfile
{
    public function __construct(
        private CurrentOrganization $current,
        private SaveMasterRecord $saveRecord,
        private ProvisionDefaultCommitteeRoles $provisionRoles,
    ) {}

    /**
     * Save the organization profile. The first save creates the organization
     * and gives it the built-in committee roles.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Organization
    {
        return DB::transaction(function () use ($attributes): Organization {
            $organization = $this->current->get() ?? new Organization;
            $isNew = ! $organization->exists;

            $this->saveRecord->handle($organization, $attributes, 'organization');

            if ($isNew) {
                $this->provisionRoles->handle($organization);
            }

            $this->current->set($organization);

            return $organization;
        });
    }
}
