<?php

namespace App\Actions\Access;

use App\Services\AuditLogger;
use App\Services\CurrentOrganization;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class SaveRole
{
    public function __construct(
        private AuditLogger $audit,
        private CurrentOrganization $organization,
    ) {}

    /**
     * Create or update an organization role with exactly the given permissions, and audit the difference.
     *
     * @param  list<string>  $permissions
     */
    public function handle(Role $role, string $name, array $permissions): Role
    {
        $permissions = array_values(array_unique($permissions));
        sort($permissions);

        return DB::transaction(function () use ($role, $name, $permissions): Role {
            $isNew = ! $role->exists;
            $nameBefore = $role->name;
            $permissionsBefore = $isNew ? [] : $this->permissionNamesOf($role);

            $role->name = $name;
            $role->guard_name = 'web';
            $role->save();

            if ($permissions !== $permissionsBefore) {
                $role->syncPermissions($permissions);
            }

            if ($isNew || $nameBefore !== $name || $permissions !== $permissionsBefore) {
                $this->audit->record(
                    action: 'roles.'.($isNew ? 'created' : 'updated'),
                    auditable: $role,
                    before: $isNew ? [] : ['name' => $nameBefore, 'permissions' => $permissionsBefore],
                    after: ['name' => $name, 'permissions' => $permissions],
                    organizationId: $this->organization->id(),
                );
            }

            return $role;
        });
    }

    /**
     * @return list<string>
     */
    private function permissionNamesOf(Role $role): array
    {
        return $role->permissions()->orderBy('name')->pluck('name')->all();
    }
}
