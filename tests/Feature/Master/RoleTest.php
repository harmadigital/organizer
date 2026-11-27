<?php

namespace Tests\Feature\Master;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\InteractsWithOrganization;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use InteractsWithOrganization;
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpOrganization();
    }

    private function superAdmin(): User
    {
        return User::factory()->create()->assignRole(PermissionSeeder::SUPER_ADMIN_ROLE);
    }

    /**
     * Manages roles but holds only some of the other permissions.
     */
    private function roleManager(): User
    {
        return $this->userWithPermissions('roles.view', 'roles.create', 'roles.update', 'roles.delete', 'venues.view', 'venues.create');
    }

    public function test_the_page_marks_permissions_the_actor_cannot_grant(): void
    {
        $response = $this->actingAs($this->roleManager())->get(route('master.role.index'))->assertOk();

        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/value="venues\.view"\s+data-permission-checkbox data-actor-holds="1"\s*>/', $html);
        $this->assertMatchesRegularExpression('/value="users\.delete"\s+data-permission-checkbox data-actor-holds="0" disabled\s*>/', $html);
        $response->assertSee('assets/js/master/role.js', false);
    }

    public function test_a_role_can_be_created_with_its_permissions_and_is_audited(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->postJson(route('master.role.insert'), [
            'name' => '  Admin   Sekretariat & <Umum> ',
            'permission' => ['venues.view', 'event-categories.view'],
        ])->assertCreated()->assertJsonPath('status', true);

        $role = Role::findByName('Admin Sekretariat & <Umum>');
        $this->assertEqualsCanonicalizing(['venues.view', 'event-categories.view'], $role->permissions->pluck('name')->all());

        $entry = AuditLog::query()->where('action', 'roles.created')->sole();
        $this->assertSame($admin->id, $entry->actor_user_id);
        $this->assertTrue($entry->auditable->is($role));
        $this->assertSame(['event-categories.view', 'venues.view'], $entry->after['permissions']);
    }

    public function test_a_role_needs_a_unique_name_and_at_least_one_permission(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->postJson(route('master.role.insert'), ['name' => '', 'permission' => []])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Kolom nama role wajib diisi.')
            ->assertJsonPath('errors.permission.0', 'Pilih minimal satu permission.');

        $this->actingAs($admin)->postJson(route('master.role.insert'), ['name' => 'Organization Admin', 'permission' => ['venues.view']])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Nama role ini sudah dipakai.');

        $this->actingAs($admin)->postJson(route('master.role.insert'), ['name' => 'Role Baru', 'permission' => ['finance.post-anything']])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['permission.0']]);
    }

    public function test_nobody_can_grant_a_permission_they_do_not_hold(): void
    {
        $manager = $this->roleManager();

        $this->actingAs($manager)->postJson(route('master.role.insert'), ['name' => 'Jalan Pintas', 'permission' => ['venues.view', 'users.manage-access']])
            ->assertStatus(422)
            ->assertJsonPath('errors.permission.0', 'Anda tidak dapat memberikan permission yang tidak Anda miliki: users.manage-access.');

        $own = Role::create(['name' => 'Pengelola Venue'])->givePermissionTo('venues.view');
        $this->actingAs($manager)->postJson(route('master.role.update', $own), ['name' => 'Pengelola Venue', 'permission' => ['venues.view', 'roles.delete', 'users.delete']])
            ->assertStatus(422);

        $this->assertNull(Role::query()->where('name', 'Jalan Pintas')->first());
        $this->assertSame(['venues.view'], $own->fresh()->permissions->pluck('name')->all());

        $this->actingAs($manager)->postJson(route('master.role.insert'), ['name' => 'Penata Venue', 'permission' => ['venues.view', 'venues.create']])
            ->assertCreated();
    }

    public function test_a_role_stronger_than_the_actor_cannot_be_edited_or_deleted(): void
    {
        $manager = $this->roleManager();
        $strong = Role::create(['name' => 'Kuat'])->givePermissionTo(['venues.view', 'users.delete']);

        $this->actingAs($manager)->postJson(route('master.role.update', $strong), ['name' => 'Kuat', 'permission' => ['venues.view']])
            ->assertForbidden();
        $this->actingAs($manager)->postJson(route('master.role.delete', $strong))
            ->assertForbidden()
            ->assertJsonPath('data', 'Anda tidak dapat menghapus role yang memiliki hak akses melebihi hak akses Anda.');
        $this->actingAs($manager)->getJson(route('master.role.show', $strong))
            ->assertOk()
            ->assertJsonPath('data.can_manage', false);

        $this->assertModelExists($strong);
        $this->assertCount(2, $strong->fresh()->permissions);
    }

    public function test_updating_a_role_replaces_its_permissions_and_audits_the_difference(): void
    {
        $role = Role::create(['name' => 'Sekretariat'])->givePermissionTo(['venues.view', 'event-categories.view']);

        $this->actingAs($this->superAdmin())->postJson(route('master.role.update', $role), [
            'name' => 'Sekretariat Umum',
            'permission' => ['venues.view', 'venues.update'],
        ])->assertOk();

        $role->refresh();
        $this->assertSame('Sekretariat Umum', $role->name);
        $this->assertEqualsCanonicalizing(['venues.view', 'venues.update'], $role->permissions->pluck('name')->all());

        $entry = AuditLog::query()->where('action', 'roles.updated')->sole();
        $this->assertSame(['name' => 'Sekretariat', 'permissions' => ['event-categories.view', 'venues.view']], $entry->before);
        $this->assertSame(['name' => 'Sekretariat Umum', 'permissions' => ['venues.update', 'venues.view']], $entry->after);
    }

    public function test_system_roles_keep_their_names_and_super_admin_cannot_be_changed(): void
    {
        $admin = $this->superAdmin();
        $organizationAdmin = Role::findByName(PermissionSeeder::ORGANIZATION_ADMIN_ROLE);
        $superAdmin = Role::findByName(PermissionSeeder::SUPER_ADMIN_ROLE);

        $this->actingAs($admin)->postJson(route('master.role.update', $organizationAdmin), ['name' => 'Admin Gereja', 'permission' => ['venues.view']])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Nama role bawaan sistem tidak dapat diubah.');

        $this->actingAs($admin)->postJson(route('master.role.update', $organizationAdmin), ['name' => 'Organization Admin', 'permission' => ['dashboard.view', 'venues.view']])
            ->assertOk();
        $this->assertEqualsCanonicalizing(['dashboard.view', 'venues.view'], $organizationAdmin->fresh()->permissions->pluck('name')->all());

        $this->actingAs($admin)->postJson(route('master.role.update', $superAdmin), ['name' => 'Super Admin', 'permission' => ['dashboard.view']])
            ->assertStatus(409)
            ->assertJsonPath('data', 'Role Super Admin selalu memiliki semua permission dan tidak dapat diubah.');
        $this->assertCount(count(PermissionSeeder::PERMISSIONS), $superAdmin->fresh()->permissions);

        foreach ([$superAdmin, $organizationAdmin] as $systemRole) {
            $this->actingAs($admin)->postJson(route('master.role.delete', $systemRole))
                ->assertStatus(409)
                ->assertJsonPath('data', 'Role bawaan sistem tidak dapat dihapus.');
            $this->assertModelExists($systemRole);
        }

        $this->actingAs($admin)->getJson(route('master.role.show', $superAdmin))
            ->assertJsonPath('data.is_system', true)
            ->assertJsonPath('data.is_locked', true);
    }

    public function test_a_role_still_held_by_users_cannot_be_deleted(): void
    {
        $role = Role::create(['name' => 'Bendahara Umum'])->givePermissionTo('venues.view');
        User::factory()->count(2)->create()->each->assignRole($role);

        $this->actingAs($this->superAdmin())->postJson(route('master.role.delete', $role))
            ->assertStatus(409)
            ->assertJsonPath('data', 'Role ini masih dipakai oleh 2 user. Ganti role mereka terlebih dahulu.');

        $this->assertModelExists($role);
    }

    public function test_an_unused_role_can_be_deleted_and_the_deletion_is_audited(): void
    {
        $role = Role::create(['name' => 'Sementara'])->givePermissionTo('venues.view');

        $this->actingAs($this->superAdmin())->postJson(route('master.role.delete', $role))->assertOk();

        $this->assertModelMissing($role);
        $this->assertSame(['name' => 'Sementara', 'permissions' => ['venues.view']], AuditLog::query()->where('action', 'roles.deleted')->sole()->before);
    }

    public function test_the_list_counts_permissions_and_holders_and_marks_what_may_be_deleted(): void
    {
        $admin = $this->superAdmin();
        $unused = Role::create(['name' => 'Tidak Dipakai'])->givePermissionTo(['venues.view', 'venues.create']);

        $rows = collect($this->actingAs($admin)->getJson(route('master.role.data'))->assertOk()->json('data'))->keyBy('name');

        $this->assertSame(2, $rows['Tidak Dipakai']['permissions_count']);
        $this->assertSame(0, $rows['Tidak Dipakai']['users_count']);
        $this->assertTrue($rows['Tidak Dipakai']['can_delete']);
        $this->assertFalse($rows['Tidak Dipakai']['is_system']);
        $this->assertSame(1, $rows['Super Admin']['users_count']);
        $this->assertFalse($rows['Super Admin']['can_delete']);
        $this->assertTrue($rows['Super Admin']['is_system']);
        $this->assertArrayNotHasKey('permissions', $rows['Tidak Dipakai']);

        $this->actingAs($admin)->getJson(route('master.role.data', ['search' => 'pakai']))
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('summary', ['total' => 3, 'permissions' => count(PermissionSeeder::PERMISSIONS), 'used' => 1]);
        $this->assertModelExists($unused);
    }

    public function test_the_list_escapes_role_names(): void
    {
        Role::create(['name' => '<b>Tebal</b>'])->givePermissionTo('venues.view');

        $this->actingAs($this->superAdmin())->getJson(route('master.role.data', ['search' => 'Tebal']))
            ->assertJsonPath('data.0.name', '&lt;b&gt;Tebal&lt;/b&gt;');
    }
}
