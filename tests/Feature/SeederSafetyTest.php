<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeederSafetyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeding_twice_changes_nothing(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(count(PermissionSeeder::PERMISSIONS), Permission::query()->count());
        $this->assertEqualsCanonicalizing(
            [PermissionSeeder::SUPER_ADMIN_ROLE, PermissionSeeder::ORGANIZATION_ADMIN_ROLE],
            Role::query()->pluck('name')->all(),
        );
        $this->assertSame(1, User::query()->count());
        $this->assertTrue(User::query()->sole()->hasRole(PermissionSeeder::SUPER_ADMIN_ROLE));
        $this->assertTrue(User::query()->sole()->can('users.delete'));
    }

    public function test_seeding_keeps_permissions_and_role_mappings_added_by_an_administrator(): void
    {
        $this->seed(DatabaseSeeder::class);

        $custom = Permission::create(['name' => 'events.create', 'guard_name' => 'web']);
        $customRole = Role::create(['name' => 'Panitia Pusat']);
        $customRole->givePermissionTo([$custom, 'users.view']);
        Role::findByName(PermissionSeeder::SUPER_ADMIN_ROLE)->givePermissionTo($custom);

        $this->seed(DatabaseSeeder::class);

        $this->assertModelExists($custom);
        $this->assertEqualsCanonicalizing(
            ['events.create', 'users.view'],
            $customRole->fresh()->permissions->pluck('name')->all(),
        );
        $this->assertTrue(Role::findByName(PermissionSeeder::SUPER_ADMIN_ROLE)->hasPermissionTo('events.create'));
    }

    public function test_the_organization_admin_role_manages_the_organization_and_its_people_but_not_roles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $organizationAdmin = Role::findByName(PermissionSeeder::ORGANIZATION_ADMIN_ROLE);

        $this->assertEqualsCanonicalizing(
            ['dashboard.view', ...PermissionSeeder::ORGANIZATION_PERMISSIONS, ...PermissionSeeder::PEOPLE_PERMISSIONS],
            $organizationAdmin->permissions->pluck('name')->all(),
        );
        $this->assertTrue($organizationAdmin->hasPermissionTo('users.manage-access'));
        $this->assertFalse($organizationAdmin->hasPermissionTo('roles.update'));
        $this->assertFalse($organizationAdmin->hasPermissionTo('permissions.create'));
    }

    public function test_seeding_removes_the_member_directory_permissions_that_merged_into_users(): void
    {
        foreach (['members.view', 'members.create', 'members.update', 'members.delete'] as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'web']);
        }

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, Permission::query()->where('name', 'like', 'members.%')->count());
        $this->assertTrue(Permission::query()->where('name', 'users.manage-access')->exists());
    }

    public function test_seeding_restores_a_permission_the_super_admin_role_lost(): void
    {
        $this->seed(DatabaseSeeder::class);
        $superAdmin = Role::findByName(PermissionSeeder::SUPER_ADMIN_ROLE);
        $superAdmin->revokePermissionTo('roles.delete');

        $this->seed(DatabaseSeeder::class);

        $this->assertTrue($superAdmin->fresh()->hasPermissionTo('roles.delete'));
    }

    public function test_seeding_removes_only_the_known_legacy_permissions(): void
    {
        foreach (['dashboard', 'master-user', 'master-role', 'master-permission', 'master-branch'] as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'web']);
        }

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, Permission::query()->whereIn('name', ['dashboard', 'master-user', 'master-role', 'master-permission'])->count());
        $this->assertTrue(Permission::query()->where('name', 'master-branch')->exists());
        $this->assertTrue(Permission::query()->where('name', 'dashboard.view')->exists());
    }

    public function test_seeding_does_not_reset_the_administrator_password(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->sole();
        $admin->forceFill(['password' => Hash::make('diganti-oleh-admin')])->save();

        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Hash::check('diganti-oleh-admin', $admin->fresh()->password));
    }

    public function test_no_administrator_with_a_default_password_is_created_outside_local_and_testing(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--force' => true])
            ->expectsOutputToContain('Administrator account not seeded')
            ->assertSuccessful();

        $this->assertSame(0, User::query()->count());
        $this->assertSame(count(PermissionSeeder::PERMISSIONS), Permission::query()->count());
    }

    public function test_the_administrator_is_created_from_configuration_when_a_password_is_provided(): void
    {
        $this->app['env'] = 'production';
        config(['organizer.seed_admin' => [
            'name' => 'Admin Gereja',
            'username' => 'admin.gereja',
            'email' => 'admin@gereja.test',
            'password' => 'Rahasia-Produksi-2026',
        ]]);

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $admin = User::query()->sole();
        $this->assertSame('admin@gereja.test', $admin->email);
        $this->assertSame('admin.gereja', $admin->username);
        $this->assertTrue(Hash::check('Rahasia-Produksi-2026', $admin->password));
        $this->assertFalse(Hash::check('123123123', $admin->password));
        $this->assertTrue($admin->hasRole(PermissionSeeder::SUPER_ADMIN_ROLE));
    }
}
