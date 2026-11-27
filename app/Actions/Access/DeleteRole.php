<?php

namespace App\Actions\Access;

use App\Services\AuditLogger;
use App\Services\CurrentOrganization;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DeleteRole
{
    public function __construct(
        private AuditLogger $audit,
        private CurrentOrganization $organization,
    ) {}

    /**
     * Delete a role nobody holds any more, keeping a record of what it granted.
     */
    public function handle(Role $role): void
    {
        DB::transaction(function () use ($role): void {
            $before = [
                'name' => $role->name,
                'permissions' => $role->permissions()->orderBy('name')->pluck('name')->all(),
            ];

            $role->delete();

            $this->audit->record(
                action: 'roles.deleted',
                auditable: $role,
                before: $before,
                organizationId: $this->organization->id(),
            );
        });
    }
}
