<?php

namespace Tests\Feature\Master;

use App\Actions\Organization\ProvisionDefaultCommitteeRoles;
use App\Enums\CommitteeRoleKind;
use App\Enums\CommitteeRoleScope;
use App\Enums\EventAbility;
use App\Models\AuditLog;
use App\Models\CommitteeRole;
use App\Models\Organization;
use Database\Seeders\CommitteeRoleSeeder;

class CommitteeRoleTest extends MasterRecordTestCase
{
    protected function routePrefix(): string
    {
        return 'master.committee_role';
    }

    protected function permissionPrefix(): string
    {
        return 'committee-roles';
    }

    protected function auditSubject(): string
    {
        return 'committee_roles';
    }

    protected function modelClass(): string
    {
        return CommitteeRole::class;
    }

    protected function pageHeading(): string
    {
        return 'Tambah Jabatan';
    }

    protected function validPayload(): array
    {
        return [
            'name' => 'Asisten Bendahara',
            'scope' => CommitteeRoleScope::Event->value,
            'description' => 'Membantu pencatatan keuangan.',
            'is_required_default' => false,
            'is_singleton_default' => true,
            'sort_order' => 45,
            'is_active' => true,
            'abilities' => [EventAbility::EventView->value, EventAbility::DocumentManage->value],
        ];
    }

    private function provisionDefaults(?Organization $organization = null): void
    {
        $this->app->make(ProvisionDefaultCommitteeRoles::class)->handle($organization ?? $this->organization);
    }

    private function builtIn(CommitteeRoleKind $kind): CommitteeRole
    {
        return CommitteeRole::query()->forOrganization($this->organization)->where('kind', $kind)->sole();
    }

    public function test_the_page_lists_every_ability_grouped_for_the_form(): void
    {
        $response = $this->actingAs($this->manager())->get($this->url('index'))->assertOk();

        foreach (EventAbility::cases() as $ability) {
            $response->assertSee('value="'.$ability->value.'"', false);
            $response->assertSee($ability->label());
        }

        $response->assertSee('data-supervisor-allowed="0"', false);
    }

    public function test_a_new_role_is_a_custom_role_with_a_generated_key_and_its_abilities(): void
    {
        $this->actingAs($this->manager())
            ->postJson($this->url('insert'), [...$this->validPayload(), 'key' => 'chair', 'kind' => 'chair'])
            ->assertCreated();

        $role = CommitteeRole::query()->sole();
        $this->assertSame('asisten_bendahara', $role->key);
        $this->assertSame(CommitteeRoleKind::Custom, $role->kind);
        $this->assertSame(CommitteeRoleScope::Event, $role->scope);
        $this->assertTrue($role->is_singleton_default);
        $this->assertSame(45, $role->sort_order);
        $this->assertSame(['document.manage', 'event.view'], $role->abilityValues());
    }

    public function test_generated_keys_stay_unique_when_names_slug_to_the_same_value(): void
    {
        $this->createRecord(['name' => 'Asisten-Bendahara', 'key' => 'asisten_bendahara']);

        $this->actingAs($this->manager())->postJson($this->url('insert'), $this->validPayload())->assertCreated();

        $this->assertSame('asisten_bendahara_2', CommitteeRole::query()->where('name', 'Asisten Bendahara')->sole()->key);
    }

    public function test_two_roles_cannot_share_a_name(): void
    {
        $this->createRecord(['name' => 'Asisten Bendahara']);

        $this->actingAs($this->manager())->postJson($this->url('insert'), $this->validPayload())
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Nama jabatan sudah digunakan.');
    }

    public function test_a_custom_role_needs_a_valid_scope(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->postJson($this->url('insert'), [...$this->validPayload(), 'scope' => null])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['scope']]);

        $this->actingAs($manager)->postJson($this->url('insert'), [...$this->validPayload(), 'scope' => 'galaxy'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['scope']]);
    }

    public function test_an_unknown_ability_is_rejected(): void
    {
        $this->actingAs($this->manager())
            ->postJson($this->url('insert'), [...$this->validPayload(), 'abilities' => ['event.view', 'finance.post-anything']])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['abilities.1']]);

        $this->assertSame(0, CommitteeRole::query()->count());
    }

    public function test_updating_a_role_replaces_its_abilities_and_audits_the_difference(): void
    {
        $role = $this->createRecord(['name' => 'Asisten Bendahara']);
        $role->abilities()->createMany([['ability' => 'event.view'], ['ability' => 'task.view']]);

        $this->actingAs($this->manager())
            ->postJson($this->url('update', [$role]), [...$this->validPayload(), 'abilities' => ['event.view', 'issue.report']])
            ->assertOk();

        $this->assertSame(['event.view', 'issue.report'], $role->abilityValues());

        $entry = AuditLog::query()->where('action', 'committee_roles.updated')->sole();
        $this->assertSame(['event.view', 'task.view'], $entry->before['abilities']);
        $this->assertSame(['event.view', 'issue.report'], $entry->after['abilities']);
    }

    public function test_saving_without_any_ability_clears_them(): void
    {
        $role = $this->createRecord(['name' => 'Asisten Bendahara']);
        $role->abilities()->create(['ability' => 'event.view']);
        $payload = $this->validPayload();
        unset($payload['abilities']);

        $this->actingAs($this->manager())->postJson($this->url('update', [$role]), $payload)->assertOk();

        $this->assertSame([], $role->abilityValues());
    }

    public function test_saving_unchanged_data_writes_no_audit_entry(): void
    {
        $this->actingAs($manager = $this->manager())->postJson($this->url('insert'), $this->validPayload())->assertCreated();
        $role = CommitteeRole::query()->sole();

        $this->actingAs($manager)->postJson($this->url('update', [$role]), $this->validPayload())->assertOk();

        $this->assertSame(0, AuditLog::query()->where('action', 'committee_roles.updated')->count());
    }

    public function test_a_record_can_be_read_with_its_abilities_and_flags(): void
    {
        $this->provisionDefaults();
        $supervisor = $this->builtIn(CommitteeRoleKind::Supervisor);

        $this->actingAs($this->manager())->getJson($this->url('show', [$supervisor]))
            ->assertOk()
            ->assertJsonPath('data.is_built_in', true)
            ->assertJsonPath('data.is_supervisory', true)
            ->assertJsonPath('data.abilities', $supervisor->abilityValues());
    }

    public function test_the_list_describes_kind_scope_and_ability_count(): void
    {
        $this->provisionDefaults();

        $this->actingAs($this->manager())->getJson($this->url('data', ['search' => 'Koordinator']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.kind_label', 'Koordinator Seksi')
            ->assertJsonPath('data.0.scope_label', 'Seksi yang ditugaskan')
            ->assertJsonPath('data.0.is_built_in', true)
            ->assertJsonPath('data.0.abilities_count', 18);
    }

    public function test_the_list_follows_committee_order_unless_the_user_sorts_it(): void
    {
        $this->provisionDefaults();
        $manager = $this->manager();

        $this->assertSame(
            ['Ketua', 'Wakil Ketua', 'Sekretaris', 'Bendahara', 'Komite / Pengawas', 'Koordinator Seksi', 'Anggota Seksi'],
            $this->actingAs($manager)->getJson($this->url('data'))->json('data.*.name'),
        );

        $sortedByName = $this->actingAs($manager)->getJson($this->url('data', [
            'columns' => [['data' => 'name', 'name' => 'name', 'orderable' => 'true', 'searchable' => 'true']],
            'order' => [['column' => 0, 'dir' => 'asc']],
        ]))->json('data.*.name');

        $this->assertSame('Anggota Seksi', $sortedByName[0]);
        $this->assertSame('Wakil Ketua', $sortedByName[6]);
    }

    public function test_an_organization_is_provisioned_with_the_seven_built_in_roles(): void
    {
        $this->provisionDefaults();

        $roles = CommitteeRole::query()->forOrganization($this->organization)->orderBy('sort_order')->get();

        $this->assertSame(
            ['chair', 'deputy', 'secretary', 'treasurer', 'supervisor', 'division_coordinator', 'division_member'],
            $roles->pluck('key')->all(),
        );
        $this->assertSame(
            ['Ketua', 'Wakil Ketua', 'Sekretaris', 'Bendahara', 'Komite / Pengawas', 'Koordinator Seksi', 'Anggota Seksi'],
            $roles->pluck('name')->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['chair', 'secretary', 'treasurer', 'supervisor'],
            $roles->where('is_required_default', true)->pluck('key')->all(),
        );
        $this->assertFalse($roles->firstWhere('key', 'deputy')->is_required_default);
        $this->assertFalse($roles->firstWhere('key', 'supervisor')->is_singleton_default);
        $this->assertEqualsCanonicalizing(
            ['division_coordinator', 'division_member'],
            $roles->where('scope', CommitteeRoleScope::Division)->pluck('key')->all(),
        );
        $roles->each(fn (CommitteeRole $role) => $this->assertNotEmpty($role->abilityValues()));
    }

    public function test_the_built_in_supervisor_holds_no_ability_that_changes_committee_work(): void
    {
        $this->provisionDefaults();

        foreach ($this->builtIn(CommitteeRoleKind::Supervisor)->abilityValues() as $ability) {
            $this->assertTrue(EventAbility::from($ability)->isAllowedForSupervisor(), $ability);
        }

        $this->assertFalse(EventAbility::TaskManage->isAllowedForSupervisor());
        $this->assertFalse(EventAbility::IssueReport->isAllowedForSupervisor());
        $this->assertTrue(EventAbility::DecisionManage->isAllowedForSupervisor());
    }

    public function test_provisioning_again_keeps_what_an_administrator_changed(): void
    {
        $this->provisionDefaults();
        $chair = $this->builtIn(CommitteeRoleKind::Chair);
        $chair->update(['name' => 'Ketua Umum', 'is_active' => false]);
        $chair->abilities()->where('ability', 'event.update')->delete();
        $abilityCount = $chair->abilities()->count();

        $this->seed(CommitteeRoleSeeder::class);

        $this->assertSame(7, CommitteeRole::query()->forOrganization($this->organization)->count());
        $this->assertSame('Ketua Umum', $chair->fresh()->name);
        $this->assertFalse($chair->fresh()->is_active);
        $this->assertSame($abilityCount, $chair->abilities()->count());
    }

    public function test_the_seeder_provisions_every_organization(): void
    {
        $other = Organization::factory()->create();

        $this->seed(CommitteeRoleSeeder::class);

        $this->assertSame(7, CommitteeRole::query()->forOrganization($this->organization)->count());
        $this->assertSame(7, CommitteeRole::query()->forOrganization($other)->count());
    }

    public function test_a_built_in_role_can_be_renamed_but_keeps_its_identity(): void
    {
        $this->provisionDefaults();
        $coordinator = $this->builtIn(CommitteeRoleKind::DivisionCoordinator);

        $this->actingAs($this->manager())->postJson($this->url('update', [$coordinator]), [
            'name' => 'Ketua Seksi',
            'scope' => CommitteeRoleScope::Event->value,
            'key' => 'something_else',
            'kind' => 'custom',
            'is_singleton_default' => true,
            'is_active' => true,
            'abilities' => $coordinator->abilityValues(),
        ])->assertOk();

        $coordinator->refresh();
        $this->assertSame('Ketua Seksi', $coordinator->name);
        $this->assertSame('division_coordinator', $coordinator->key);
        $this->assertSame(CommitteeRoleKind::DivisionCoordinator, $coordinator->kind);
        $this->assertSame(CommitteeRoleScope::Division, $coordinator->scope);
    }

    public function test_a_built_in_role_cannot_be_deleted(): void
    {
        $this->provisionDefaults();
        $treasurer = $this->builtIn(CommitteeRoleKind::Treasurer);

        $this->actingAs($this->manager())->postJson($this->url('delete', [$treasurer]))
            ->assertStatus(409)
            ->assertJsonPath('data', 'Jabatan bawaan tidak dapat dihapus. Nonaktifkan saja bila tidak digunakan.');

        $this->assertModelExists($treasurer);
    }

    public function test_a_supervisor_cannot_be_given_an_ability_that_changes_committee_work(): void
    {
        $this->provisionDefaults();
        $supervisor = $this->builtIn(CommitteeRoleKind::Supervisor);
        $before = $supervisor->abilityValues();

        $this->actingAs($this->manager())->postJson($this->url('update', [$supervisor]), [
            'name' => $supervisor->name,
            'is_required_default' => true,
            'is_active' => true,
            'abilities' => [...$before, EventAbility::TaskManage->value],
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.abilities.0', 'Jabatan pengawas hanya boleh melihat data dan mencatat keputusan, tidak mengubah pekerjaan panitia.');

        $this->assertSame($before, $supervisor->abilityValues());
        $this->assertSame(0, AuditLog::query()->where('action', 'committee_roles.updated')->count());
    }

    public function test_a_supervisor_can_be_given_more_read_abilities(): void
    {
        $this->provisionDefaults();
        $supervisor = $this->builtIn(CommitteeRoleKind::Supervisor);
        $supervisor->abilities()->where('ability', 'document.view')->delete();

        $this->actingAs($this->manager())->postJson($this->url('update', [$supervisor]), [
            'name' => $supervisor->name,
            'is_required_default' => true,
            'is_active' => true,
            'abilities' => [...$supervisor->abilityValues(), EventAbility::DocumentView->value],
        ])->assertOk();

        $this->assertContains('document.view', $supervisor->abilityValues());
    }
}
