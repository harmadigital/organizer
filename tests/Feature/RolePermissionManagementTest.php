<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PermissionSeeder;
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
            'total' => 3,
            'permissions' => count(PermissionSeeder::PERMISSIONS),
            'used' => 2,
        ], $response->viewData('roleSummary'));
        $response->assertSeeInOrder(['ui-page-header', 'ui-subnav', 'ui-stat-card', 'ui-toolbar', 'kt_datatable_master', 'kt_modal_master'], false);
        $response->assertSee('Total Role');
        $response->assertSee('Total Permission');
        $response->assertSee('Role Digunakan');
        $response->assertSee('Tambah Role');
        $response->assertSee('Pilih Semua');
        $response->assertSee('Cari modul, aksi, atau kode permission...');
        $response->assertSee('users.view');
        $response->assertSee('users.delete');
        $response->assertSee('Role & Permission');
        $response->assertSee('Unit Pelayanan');
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
        $this->assertSame(count(PermissionSeeder::PERMISSIONS), Permission::query()->count());
        $this->assertEqualsCanonicalizing(
            PermissionSeeder::PERMISSIONS,
            Permission::query()->pluck('name')->all(),
        );
        foreach (['dashboard.view', 'users.view', 'users.reset-two-factor', 'roles.delete', 'permissions.update'] as $permission) {
            $this->assertContains($permission, PermissionSeeder::PERMISSIONS);
        }
        $this->assertSame('dashboard.view', Permission::findByName('dashboard.view')->name);
    }
}
