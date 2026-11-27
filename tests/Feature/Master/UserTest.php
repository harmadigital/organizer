<?php

namespace Tests\Feature\Master;

use App\Enums\UserStatus;
use App\Http\Middleware\EnsureUserCanSignIn;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithOrganization;
use Tests\TestCase;

class UserTest extends TestCase
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

    private function organizationAdmin(): User
    {
        return User::factory()->create()->assignRole(PermissionSeeder::ORGANIZATION_ADMIN_ROLE);
    }

    /**
     * Someone who maintains the directory but may not hand out login access.
     */
    private function directoryEditor(): User
    {
        return $this->userWithPermissions('users.view', 'users.create', 'users.update', 'users.delete');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function person(array $overrides = []): array
    {
        return [
            'name' => 'Debora Simanjuntak',
            'email' => 'debora@example.com',
            'phone' => '081234567890',
            'status' => UserStatus::Active->value,
            'notes' => 'Pernah menjadi bendahara Natal.',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function personWithLogin(array $overrides = []): array
    {
        return [
            ...$this->person(),
            'login_enabled' => '1',
            'username' => 'debora',
            'password' => 'Rahasia-2026',
            'password_confirmation' => 'Rahasia-2026',
            'role' => '',
            ...$overrides,
        ];
    }

    /**
     * The directory part of an existing person, as the form would send it back unchanged.
     *
     * @return array<string, mixed>
     */
    private function directoryOf(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status->value,
            'notes' => $user->notes,
        ];
    }

    private function withTwoFactor(User $user): User
    {
        $user->forceFill([
            'two_factor_secret' => encrypt('secret-key'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user;
    }

    public function test_the_page_follows_the_list_pattern_and_offers_access_management_to_those_allowed(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('master.user.index'));

        $response->assertOk()
            ->assertSeeInOrder(['ui-page-header', 'ui-subnav', 'ui-stat-card', 'ui-toolbar', 'kt_datatable_master', 'kt_modal_master', 'kt_modal_guide'], false)
            ->assertSee('data-user-access', false)
            ->assertSee('data-user-reset-two-factor', false)
            ->assertSee('assets/js/master/user.js', false);
        $this->assertSame(['Organization Admin', 'Super Admin'], $response->viewData('grantableRoles')->all());
    }

    public function test_a_directory_editor_gets_no_access_section(): void
    {
        $this->actingAs($this->directoryEditor())->get(route('master.user.index'))
            ->assertOk()
            ->assertSee('data-master-add', false)
            ->assertDontSee('data-user-access', false);
    }

    public function test_an_organization_admin_is_only_offered_roles_they_may_grant(): void
    {
        $response = $this->actingAs($this->organizationAdmin())->get(route('master.user.index'))->assertOk();

        $this->assertSame(['Organization Admin'], $response->viewData('grantableRoles')->all());
    }

    public function test_a_user_without_permission_cannot_open_the_screen(): void
    {
        $someone = User::factory()->create();
        $outsider = $this->userWithPermissions('dashboard.view');

        $this->actingAs($outsider)->get(route('master.user.index'))->assertForbidden();
        $this->actingAs($outsider)->getJson(route('master.user.data'))->assertForbidden();
        $this->actingAs($outsider)->getJson(route('master.user.show', $someone))->assertForbidden();
    }

    public function test_a_viewer_cannot_change_anything(): void
    {
        $someone = User::factory()->create();
        $viewer = $this->userWithPermissions('users.view');

        $this->actingAs($viewer)->postJson(route('master.user.insert'), $this->person())->assertForbidden();
        $this->actingAs($viewer)->postJson(route('master.user.update', $someone), $this->person())->assertForbidden();
        $this->actingAs($viewer)->postJson(route('master.user.delete', $someone))->assertForbidden();
        $this->actingAs($viewer)->postJson(route('master.user.reset_2fa', $someone))->assertForbidden();

        $this->assertSame(2, User::query()->count());
    }

    public function test_a_person_can_be_recorded_without_login_access(): void
    {
        $editor = $this->directoryEditor();

        $this->actingAs($editor)
            ->postJson(route('master.user.insert'), $this->person(['email' => '  Debora.S@Example.COM ', 'phone' => '+62 812-3456 (7890)']))
            ->assertCreated()
            ->assertJsonPath('status', true);

        $person = User::query()->where('name', 'Debora Simanjuntak')->sole();
        $this->assertNull($person->username);
        $this->assertNull($person->password);
        $this->assertFalse($person->hasLoginAccess());
        $this->assertFalse($person->canSignIn());
        $this->assertSame('debora.s@example.com', $person->email);
        $this->assertSame('+6281234567890', $person->phone);
        $this->assertSame(UserStatus::Active, $person->status);

        $entry = AuditLog::query()->where('action', 'users.created')->sole();
        $this->assertSame($editor->id, $entry->actor_user_id);
        $this->assertSame($this->organization->id, $entry->organization_id);
        $this->assertTrue($entry->auditable->is($person));
        $this->assertSame('Debora Simanjuntak', $entry->after['name']);
        $this->assertSame([], $entry->after['roles']);
    }

    public function test_only_a_name_and_a_status_are_required(): void
    {
        $editor = $this->directoryEditor();

        $this->actingAs($editor)->postJson(route('master.user.insert'), ['name' => 'Tanpa Kontak', 'status' => 'active'])
            ->assertCreated();

        $this->actingAs($editor)->postJson(route('master.user.insert'), ['name' => '', 'status' => 'retired'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Kolom nama lengkap wajib diisi.')
            ->assertJsonStructure(['errors' => ['status']]);
    }

    public function test_several_people_may_have_no_email_phone_or_username(): void
    {
        $editor = $this->directoryEditor();

        $this->actingAs($editor)->postJson(route('master.user.insert'), ['name' => 'Pertama', 'status' => 'active'])->assertCreated();
        $this->actingAs($editor)->postJson(route('master.user.insert'), ['name' => 'Kedua', 'status' => 'active', 'email' => '', 'phone' => ''])->assertCreated();

        $this->assertSame(2, User::query()->whereNull('email')->whereNull('phone')->whereNull('username')->count());
    }

    public function test_two_people_cannot_share_an_email_or_a_phone_number(): void
    {
        User::factory()->create(['email' => 'debora@example.com', 'phone' => '081234567890']);
        $editor = $this->directoryEditor();

        $this->actingAs($editor)->postJson(route('master.user.insert'), $this->person(['email' => 'DEBORA@example.com', 'phone' => '0899999999']))
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Email ini sudah dipakai user lain.');

        $this->actingAs($editor)->postJson(route('master.user.insert'), $this->person(['email' => 'lain@example.com', 'phone' => '0812-3456-7890']))
            ->assertStatus(422)
            ->assertJsonPath('errors.phone.0', 'Nomor telepon ini sudah dipakai user lain.');

        $this->actingAs($editor)->postJson(route('master.user.insert'), $this->person(['phone' => 'telepon rumah', 'email' => 'x@example.com']))
            ->assertStatus(422)
            ->assertJsonPath('errors.phone.0', 'Nomor telepon harus terdiri dari 8 sampai 15 angka, boleh diawali tanda +.');
    }

    public function test_a_person_keeps_their_own_email_and_phone_when_edited(): void
    {
        $person = User::factory()->create(['email' => 'debora@example.com', 'phone' => '081234567890']);

        $this->actingAs($this->directoryEditor())
            ->postJson(route('master.user.update', $person), $this->person(['name' => 'Debora S.']))
            ->assertOk();

        $this->assertSame('Debora S.', $person->fresh()->name);
    }

    public function test_the_unit_must_belong_to_the_current_organization(): void
    {
        $unit = OrganizationUnit::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Komisi Pemuda']);
        $foreignUnit = OrganizationUnit::factory()->create(['organization_id' => Organization::factory()->create()->id]);
        $editor = $this->directoryEditor();

        $this->actingAs($editor)->postJson(route('master.user.insert'), $this->person(['organization_unit_id' => $foreignUnit->id]))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['organization_unit_id']]);

        $this->actingAs($editor)->postJson(route('master.user.insert'), $this->person(['organization_unit_id' => $unit->id]))
            ->assertCreated();

        $this->actingAs($editor)->getJson(route('master.user.data', ['search' => 'Debora']))
            ->assertJsonPath('data.0.unit_name', 'Komisi Pemuda');
    }

    public function test_a_person_given_login_access_can_sign_in_with_their_role(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson(route('master.user.insert'), $this->personWithLogin(['username' => '  Debora.S ', 'role' => 'Organization Admin']))
            ->assertCreated();

        $person = User::query()->where('name', 'Debora Simanjuntak')->sole();
        $this->assertSame('debora.s', $person->username);
        $this->assertTrue(Hash::check('Rahasia-2026', $person->password));
        $this->assertTrue($person->hasRole('Organization Admin'));

        $entry = AuditLog::query()->where('action', 'users.created')->sole();
        $this->assertSame('[REDACTED]', $entry->after['password']);
        $this->assertSame(['Organization Admin'], $entry->after['roles']);

        $this->post('/logout');
        $this->post('/login', ['username' => 'debora.s', 'password' => 'Rahasia-2026']);

        $this->assertAuthenticatedAs($person);
    }

    public function test_login_access_needs_a_username_and_a_first_password(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson(route('master.user.insert'), $this->personWithLogin(['username' => '', 'password' => '', 'password_confirmation' => '']))
            ->assertStatus(422)
            ->assertJsonPath('errors.username.0', 'Nama pengguna wajib diisi bila akses login diaktifkan.')
            ->assertJsonPath('errors.password.0', 'Kata sandi wajib diisi saat akses login pertama kali diaktifkan.');

        $this->assertSame(0, User::query()->where('name', 'Debora Simanjuntak')->count());
    }

    public function test_the_username_must_be_well_formed_and_unique(): void
    {
        User::factory()->create(['username' => 'debora']);
        $admin = $this->superAdmin();

        $this->actingAs($admin)->postJson(route('master.user.insert'), $this->personWithLogin(['username' => 'Debora']))
            ->assertStatus(422)
            ->assertJsonPath('errors.username.0', 'Nama pengguna ini sudah dipakai user lain.');

        $this->actingAs($admin)->postJson(route('master.user.insert'), $this->personWithLogin(['username' => 'debora simanjuntak']))
            ->assertStatus(422)
            ->assertJsonPath('errors.username.0', 'Nama pengguna hanya boleh berisi huruf kecil, angka, titik, garis bawah, dan tanda hubung.');

        $this->actingAs($admin)->postJson(route('master.user.insert'), $this->personWithLogin(['password_confirmation' => 'berbeda-2026']))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['password']]);
    }

    public function test_the_password_is_optional_once_set_and_a_new_one_ends_remembered_sessions(): void
    {
        $person = User::factory()->create(['username' => 'debora']);
        $originalHash = $person->password;
        $person->setRememberToken('token-lama');
        $person->save();
        $admin = $this->superAdmin();
        $keepPassword = [...$this->directoryOf($person), 'login_enabled' => '1', 'username' => 'debora', 'password' => '', 'password_confirmation' => ''];

        $this->actingAs($admin)->postJson(route('master.user.update', $person), $keepPassword)->assertOk();
        $this->assertSame($originalHash, $person->fresh()->password);
        $this->assertSame('token-lama', $person->fresh()->getRememberToken());

        $this->actingAs($admin)->postJson(route('master.user.update', $person), [...$keepPassword, 'password' => 'Sandi-Baru-2026', 'password_confirmation' => 'Sandi-Baru-2026'])
            ->assertOk();
        $this->assertTrue(Hash::check('Sandi-Baru-2026', $person->fresh()->password));
        $this->assertNotSame('token-lama', $person->fresh()->getRememberToken());
    }

    public function test_a_directory_editor_cannot_touch_login_access(): void
    {
        $person = User::factory()->create();
        $originalHash = $person->password;
        $editor = $this->directoryEditor();

        $this->actingAs($editor)->postJson(route('master.user.insert'), $this->personWithLogin())->assertForbidden();
        $this->actingAs($editor)->postJson(route('master.user.update', $person), [...$this->directoryOf($person), 'login_enabled' => '0'])
            ->assertForbidden();

        $this->actingAs($editor)->postJson(route('master.user.update', $person), [...$this->directoryOf($person), 'name' => 'Nama Baru'])
            ->assertOk();

        $this->assertSame('Nama Baru', $person->fresh()->name);
        $this->assertSame($originalHash, $person->fresh()->password);
        $this->assertTrue($person->fresh()->canSignIn());
    }

    public function test_revoking_login_access_clears_credentials_and_signs_the_person_out(): void
    {
        $person = $this->withTwoFactor(User::factory()->create()->assignRole(PermissionSeeder::ORGANIZATION_ADMIN_ROLE));

        $this->actingAs($this->superAdmin())
            ->postJson(route('master.user.update', $person), [...$this->directoryOf($person), 'login_enabled' => '0', 'username' => $person->username])
            ->assertOk();

        $person->refresh();
        $this->assertNull($person->password);
        $this->assertNull($person->two_factor_secret);
        $this->assertNull($person->two_factor_confirmed_at);
        $this->assertSame([], $person->getRoleNames()->all());
        $this->assertFalse($person->canSignIn());

        $entry = AuditLog::query()->where('action', 'users.updated')->sole();
        $this->assertSame(['Organization Admin'], $entry->before['roles']);
        $this->assertSame([], $entry->after['roles']);
        $this->assertSame('[REDACTED]', $entry->before['password']);

        $this->actingAs($person)->get(route('dashboard.index'))
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors(['username' => EnsureUserCanSignIn::MESSAGE]);
        $this->assertGuest();
    }

    public function test_deactivating_a_person_signs_them_out_on_their_next_request(): void
    {
        $person = $this->userWithPermissions('venues.view');

        $this->actingAs($this->directoryEditor())
            ->postJson(route('master.user.update', $person), [...$this->directoryOf($person), 'status' => UserStatus::Inactive->value])
            ->assertOk();

        $this->assertFalse($person->fresh()->canSignIn());
        $this->assertTrue($person->fresh()->hasLoginAccess());

        $this->actingAs($person->fresh())->getJson(route('master.venue.data'))
            ->assertUnauthorized()
            ->assertJsonPath('data', EnsureUserCanSignIn::MESSAGE);
        $this->assertGuest();
    }

    public function test_an_inactive_person_or_one_without_a_password_cannot_sign_in(): void
    {
        $inactive = User::factory()->inactive()->create(['username' => 'nonaktif']);
        User::factory()->create(['username' => 'tanpa.sandi', 'password' => null]);

        $this->post('/login', ['username' => 'nonaktif', 'password' => 'password'])
            ->assertSessionHasErrors(['username' => 'Nama pengguna atau kata sandi tidak cocok dengan data kami.']);
        $this->post('/login', ['username' => 'tanpa.sandi', 'password' => 'password']);

        $this->assertGuest();
        $this->assertTrue(Hash::check('password', $inactive->password));
    }

    public function test_nobody_can_lock_themselves_out_or_change_their_own_privileges(): void
    {
        $admin = $this->superAdmin();
        $ownAccess = [...$this->directoryOf($admin), 'login_enabled' => '1', 'username' => $admin->username, 'role' => 'Super Admin'];

        $this->actingAs($admin)->postJson(route('master.user.update', $admin), [...$this->directoryOf($admin), 'status' => 'inactive'])
            ->assertStatus(422)
            ->assertJsonPath('errors.status.0', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');

        $this->actingAs($admin)->postJson(route('master.user.update', $admin), [...$ownAccess, 'login_enabled' => '0'])
            ->assertStatus(422)
            ->assertJsonPath('errors.login_enabled.0', 'Anda tidak dapat mencabut akses login Anda sendiri.');

        $this->actingAs($admin)->postJson(route('master.user.update', $admin), [...$ownAccess, 'role' => 'Organization Admin'])
            ->assertStatus(422)
            ->assertJsonPath('errors.role.0', 'Anda tidak dapat mengubah role Anda sendiri.');

        $this->actingAs($admin)->postJson(route('master.user.delete', $admin))
            ->assertStatus(409)
            ->assertJsonPath('data', 'Anda tidak dapat menghapus akun Anda sendiri.');

        $this->actingAs($admin)->postJson(route('master.user.update', $admin), [...$ownAccess, 'name' => 'Nama Saya'])
            ->assertOk();

        $this->assertTrue($admin->fresh()->canSignIn());
        $this->assertTrue($admin->fresh()->hasRole('Super Admin'));
        $this->assertSame('Nama Saya', $admin->fresh()->name);
    }

    public function test_an_organization_admin_cannot_hand_out_a_stronger_role(): void
    {
        $organizationAdmin = $this->organizationAdmin();

        $this->actingAs($organizationAdmin)->postJson(route('master.user.insert'), $this->personWithLogin(['role' => 'Super Admin']))
            ->assertStatus(422)
            ->assertJsonPath('errors.role.0', 'Anda tidak dapat memberikan role yang memiliki hak akses melebihi hak akses Anda.');

        $this->actingAs($organizationAdmin)->postJson(route('master.user.insert'), $this->personWithLogin(['role' => 'Organization Admin']))
            ->assertCreated();

        $this->assertTrue(User::query()->where('username', 'debora')->sole()->hasRole('Organization Admin'));
    }

    public function test_an_organization_admin_cannot_manage_access_of_a_stronger_account(): void
    {
        $superAdmin = $this->withTwoFactor($this->superAdmin());
        $organizationAdmin = $this->organizationAdmin();

        $this->actingAs($organizationAdmin)
            ->postJson(route('master.user.update', $superAdmin), [...$this->directoryOf($superAdmin), 'login_enabled' => '1', 'username' => $superAdmin->username, 'password' => 'Ambil-Alih-2026', 'password_confirmation' => 'Ambil-Alih-2026', 'role' => 'Super Admin'])
            ->assertForbidden();
        $this->actingAs($organizationAdmin)->postJson(route('master.user.delete', $superAdmin))
            ->assertForbidden()
            ->assertJsonPath('data', 'Anda tidak dapat menghapus user yang memiliki hak akses melebihi hak akses Anda.');
        $this->actingAs($organizationAdmin)->postJson(route('master.user.reset_2fa', $superAdmin))->assertForbidden();
        $this->actingAs($organizationAdmin)->getJson(route('master.user.show', $superAdmin))
            ->assertOk()
            ->assertJsonPath('data.can_manage_access', false);

        $this->actingAs($organizationAdmin)->postJson(route('master.user.update', $superAdmin), [...$this->directoryOf($superAdmin), 'phone' => '0811111111'])
            ->assertOk();

        $superAdmin->refresh();
        $this->assertTrue(Hash::check('password', $superAdmin->password));
        $this->assertNotNull($superAdmin->two_factor_confirmed_at);
        $this->assertSame('0811111111', $superAdmin->phone);
    }

    public function test_the_list_can_be_searched_and_filtered(): void
    {
        $unit = OrganizationUnit::factory()->create(['organization_id' => $this->organization->id]);
        $admin = $this->superAdmin();
        $inUnit = User::factory()->withoutLogin()->create(['name' => 'Yohana', 'phone' => '082222222222', 'organization_unit_id' => $unit->id]);
        $inactive = User::factory()->inactive()->create(['name' => 'Markus', 'username' => 'markus.p']);
        $organizationAdmin = $this->organizationAdmin();

        $ids = fn (array $parameters): array => $this->actingAs($admin)->getJson(route('master.user.data', $parameters))->assertOk()->json('data.*.id');

        $this->assertSame([$inactive->id], $ids(['search' => 'markus.p']));
        $this->assertSame([$inUnit->id], $ids(['search' => '08222']));
        $this->assertSame([$inUnit->id], $ids(['organization_unit_id' => $unit->id]));
        $this->assertSame([$inUnit->id], $ids(['access' => 'without']));
        $this->assertEqualsCanonicalizing([$admin->id, $inactive->id, $organizationAdmin->id], $ids(['access' => 'with']));
        $this->assertSame([$inactive->id], $ids(['status' => 'inactive']));
        $this->assertSame([$organizationAdmin->id], $ids(['role' => 'Organization Admin']));
    }

    public function test_the_list_never_exposes_secrets_and_escapes_text(): void
    {
        $admin = $this->superAdmin();
        $this->withTwoFactor(User::factory()->create(['name' => '<script>alert(1)</script>']));

        $response = $this->actingAs($admin)->getJson(route('master.user.data', ['search' => 'script']))->assertOk();

        $row = $response->json('data.0');
        foreach (['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'has_password', 'roles', 'permissions'] as $hidden) {
            $this->assertArrayNotHasKey($hidden, $row);
        }
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $row['name']);
        $this->assertTrue($row['has_login']);
        $this->assertStringNotContainsString('$2y$', $response->getContent());
    }

    public function test_the_list_carries_a_fresh_summary_and_marks_who_may_be_deleted(): void
    {
        $admin = $this->superAdmin();
        $other = User::factory()->withoutLogin()->create();
        User::factory()->inactive()->create();

        $response = $this->actingAs($admin)->getJson(route('master.user.data', ['search' => 'tidak ada yang cocok']))->assertOk();
        $this->assertSame(['total' => 3, 'active' => 2, 'with_login' => 2], $response->json('summary'));

        $rows = collect($this->actingAs($admin)->getJson(route('master.user.data'))->json('data'))->keyBy('id');
        $this->assertFalse($rows[$admin->id]['can_delete']);
        $this->assertTrue($rows[$other->id]['can_delete']);
        $this->assertSame('Super Admin', $rows[$admin->id]['role_names']);
        $this->assertFalse($rows[$other->id]['has_login']);
    }

    public function test_a_person_can_be_read_for_editing_without_secrets(): void
    {
        $admin = $this->superAdmin();
        $person = $this->withTwoFactor(User::factory()->create(['name' => 'Ibadah & Doa <Raya>'])->assignRole('Organization Admin'));

        $data = $this->actingAs($admin)->getJson(route('master.user.show', $person))->assertOk()->json('data');

        $this->assertSame('Ibadah & Doa <Raya>', $data['name']);
        $this->assertSame($person->username, $data['username']);
        $this->assertSame('Organization Admin', $data['role']);
        $this->assertTrue($data['login_enabled']);
        $this->assertTrue($data['two_factor_enabled']);
        $this->assertTrue($data['can_manage_access']);
        $this->assertFalse($data['is_self']);
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('two_factor_secret', $data);
    }

    public function test_an_unused_person_can_be_deleted_and_the_deletion_is_audited_without_secrets(): void
    {
        $person = User::factory()->create(['name' => 'Akan Dihapus'])->assignRole('Organization Admin');

        $this->actingAs($this->superAdmin())->postJson(route('master.user.delete', $person))
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertModelMissing($person);
        $this->assertDatabaseMissing('model_has_roles', ['model_id' => $person->id]);

        $entry = AuditLog::query()->where('action', 'users.deleted')->sole();
        $this->assertSame('Akan Dihapus', $entry->before['name']);
        $this->assertSame('[REDACTED]', $entry->before['password']);
    }

    public function test_two_factor_login_can_be_reset_by_an_access_manager(): void
    {
        $person = $this->withTwoFactor(User::factory()->create());

        $this->actingAs($this->directoryEditor())->postJson(route('master.user.reset_2fa', $person))->assertForbidden();

        $this->actingAs($this->superAdmin())->postJson(route('master.user.reset_2fa', $person))->assertOk();

        $person->refresh();
        $this->assertNull($person->two_factor_secret);
        $this->assertNull($person->two_factor_recovery_codes);
        $this->assertNull($person->two_factor_confirmed_at);
        $this->assertTrue($person->canSignIn());
        $this->assertTrue(AuditLog::query()->where('action', 'users.two_factor_reset')->where('auditable_id', $person->id)->exists());
    }

    public function test_the_user_screen_works_before_the_organization_profile_exists(): void
    {
        Organization::query()->delete();
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get(route('master.user.index'))->assertOk();
        $this->actingAs($admin)->postJson(route('master.user.insert'), $this->person())->assertCreated();
    }
}
