<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public const SUPER_ADMIN_ROLE = 'Super Admin';

    public const ORGANIZATION_ADMIN_ROLE = 'Organization Admin';

    /**
     * Roles this seeder looks up by name; renaming or deleting them would break it.
     *
     * @var list<string>
     */
    public const SYSTEM_ROLES = [self::SUPER_ADMIN_ROLE, self::ORGANIZATION_ADMIN_ROLE];

    /**
     * Organization-level permissions the application checks for.
     *
     * @var list<string>
     */
    public const PERMISSIONS = [
        'dashboard.view',
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'users.reset-two-factor',
        'users.manage-access',
        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',
        'permissions.view',
        'permissions.create',
        'permissions.update',
        'permissions.delete',
        ...self::ORGANIZATION_PERMISSIONS,
        ...self::EVENT_PERMISSIONS,
    ];

    /**
     * Managing the organization itself: its profile and master data.
     *
     * @var list<string>
     */
    public const ORGANIZATION_PERMISSIONS = [
        'organization.view',
        'organization.update',
        'organization-units.view',
        'organization-units.create',
        'organization-units.update',
        'organization-units.delete',
        'committee-roles.view',
        'committee-roles.create',
        'committee-roles.update',
        'committee-roles.delete',
        'division-templates.view',
        'division-templates.create',
        'division-templates.update',
        'division-templates.delete',
        'event-categories.view',
        'event-categories.create',
        'event-categories.update',
        'event-categories.delete',
        'venues.view',
        'venues.create',
        'venues.update',
        'venues.delete',
    ];

    /**
     * Running events across the organization. Inside an event, what someone may do
     * comes from their committee roles; these permissions reach every event.
     *
     * @var list<string>
     */
    public const EVENT_PERMISSIONS = [
        'events.view',
        'events.create',
        'events.update',
        'events.manage-committee',
        'events.change-status',
    ];

    /**
     * Managing people and their login access. Assigning a role or managing an account
     * is further limited by AccountAuthority to roles and people no stronger than oneself.
     *
     * @var list<string>
     */
    public const PEOPLE_PERMISSIONS = [
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'users.reset-two-factor',
        'users.manage-access',
    ];

    /**
     * Names no code checks anymore: those of the first version of the boilerplate, and
     * the member directory permissions that existed before members merged into users.
     *
     * @var list<string>
     */
    private const LEGACY_PERMISSIONS = [
        'dashboard',
        'master-user',
        'master-role',
        'master-permission',
        'members.view',
        'members.create',
        'members.update',
        'members.delete',
    ];

    /**
     * Add what is missing and leave everything else untouched, so it is safe to run repeatedly.
     */
    public function run(PermissionRegistrar $registrar): void
    {
        $registrar->forgetCachedPermissions();

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', self::LEGACY_PERMISSIONS)
            ->get()
            ->each
            ->delete();

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $registrar->forgetCachedPermissions();

        Role::firstOrCreate(['name' => self::SUPER_ADMIN_ROLE, 'guard_name' => 'web'])
            ->givePermissionTo(self::PERMISSIONS);

        Role::firstOrCreate(['name' => self::ORGANIZATION_ADMIN_ROLE, 'guard_name' => 'web'])
            ->givePermissionTo(['dashboard.view', ...self::ORGANIZATION_PERMISSIONS, ...self::PEOPLE_PERMISSIONS, ...self::EVENT_PERMISSIONS]);

        $registrar->forgetCachedPermissions();
    }
}
