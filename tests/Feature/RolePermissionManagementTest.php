<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_displays_granular_permissions_grouped_by_module_on_the_role_screen(): void
    {
        $this->seed(DatabaseSeeder::class);

        $permissions = Permission::query()
            ->whereIn('name', ['roles.view', 'roles.create', 'users.view', 'users.delete'])
            ->get();

        $role = Role::create(['name' => 'Role Manager']);
        $role->givePermissionTo($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('master.role.index'));

        $response->assertOk();
        $this->assertSame([
            'total' => 2,
            'permissions' => 14,
            'used' => 2,
        ], $response->viewData('roleSummary'));
        $response->assertSeeInOrder([
            'data-role-section="header"',
            'data-role-section="counter"',
            'data-role-section="filter"',
            'data-role-section="list"',
        ], false);
        $response->assertSee('Total Role');
        $response->assertSee('Total Permission');
        $response->assertSee('Role Digunakan');
        $response->assertSee('List Data Role');
        $response->assertSee('Pilih Semua');
        $response->assertSee('Cari modul, aksi, atau kode permission...');
        $response->assertSee('users.view');
        $response->assertSee('users.delete');
        $response->assertSee('Role & Permission');
    }

    public function test_it_requires_the_matching_granular_permission_for_each_role_action(): void
    {
        $this->seed(DatabaseSeeder::class);

        $role = Role::create(['name' => 'Role Viewer']);
        $role->givePermissionTo('roles.view');

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->post(route('master.role.insert'), ['name' => 'New Role', 'permission' => ['roles.view']])
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('master.role.index'))
            ->assertOk();
    }

    public function test_it_replaces_legacy_permissions_when_seeding_the_boilerplate(): void
    {
        Permission::create(['name' => 'master-role', 'guard_name' => 'web']);

        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Permission::query()->where('name', 'master-role')->doesntExist());
        $this->assertSame(14, Permission::query()->count());
        $this->assertSame(
            ['dashboard.view', 'permissions.create', 'permissions.delete', 'permissions.update', 'permissions.view', 'roles.create', 'roles.delete', 'roles.update', 'roles.view', 'users.create', 'users.delete', 'users.reset-two-factor', 'users.update', 'users.view'],
            Permission::query()->orderBy('name')->pluck('name')->all(),
        );
        $this->assertSame('dashboard.view', Permission::findByName('dashboard.view')->name);
    }
}
