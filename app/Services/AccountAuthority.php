<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * Decides whose login access a user may manage, so nobody can raise their own
 * privileges by managing an account, or handing out a role, that is stronger than theirs.
 */
class AccountAuthority
{
    /**
     * The actor may change the target's login access, reset their two-factor login or
     * remove them only when the target holds no permission the actor lacks.
     */
    public function canManage(User $actor, User $target): bool
    {
        return $this->holdsAll($actor, $target->getAllPermissions()->pluck('name')->all());
    }

    /**
     * The actor may hand out a role only when they hold every permission it grants.
     */
    public function canGrantRole(User $actor, Role $role): bool
    {
        return $this->holdsAll($actor, $role->permissions->pluck('name')->all());
    }

    /**
     * Organization roles the actor may hand out, by name.
     *
     * @return Collection<int, Role>
     */
    public function grantableRoles(User $actor): Collection
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->filter(fn (Role $role): bool => $this->canGrantRole($actor, $role))
            ->values();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function holdsAll(User $actor, array $permissions): bool
    {
        return array_diff($permissions, $actor->getAllPermissions()->pluck('name')->all()) === [];
    }
}
