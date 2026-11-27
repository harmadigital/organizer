<?php

namespace Tests\Feature\Master;

use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\User;

class OrganizationUnitTest extends MasterRecordTestCase
{
    protected function routePrefix(): string
    {
        return 'master.organization_unit';
    }

    protected function permissionPrefix(): string
    {
        return 'organization-units';
    }

    protected function auditSubject(): string
    {
        return 'organization_units';
    }

    protected function modelClass(): string
    {
        return OrganizationUnit::class;
    }

    protected function pageHeading(): string
    {
        return 'Tambah Unit';
    }

    protected function validPayload(): array
    {
        return ['name' => 'Komisi Pemuda', 'description' => 'Pelayanan pemuda dan remaja.', 'is_active' => true];
    }

    public function test_two_units_cannot_share_a_name(): void
    {
        $this->createRecord(['name' => 'Komisi Anak']);

        $this->actingAs($this->manager())->postJson($this->url('insert'), [...$this->validPayload(), 'name' => 'Komisi Anak'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'nama unit sudah digunakan.');
    }

    public function test_a_unit_can_be_placed_under_another_unit(): void
    {
        $parent = $this->createRecord(['name' => 'Bidang Pembinaan']);

        $this->actingAs($manager = $this->manager())
            ->postJson($this->url('insert'), [...$this->validPayload(), 'parent_id' => $parent->id])
            ->assertCreated();

        $child = OrganizationUnit::query()->where('name', 'Komisi Pemuda')->sole();
        $this->assertTrue($child->parent->is($parent));

        $this->actingAs($manager)->getJson($this->url('data', ['search' => 'Pemuda']))
            ->assertJsonPath('data.0.parent.name', 'Bidang Pembinaan')
            ->assertJsonPath('data.0.users_count', 0);
    }

    public function test_the_parent_name_is_escaped_in_the_list(): void
    {
        $parent = $this->createRecord(['name' => '<b>Induk</b>']);
        $this->createRecord(['name' => 'Anak', 'parent_id' => $parent->id]);

        $response = $this->actingAs($this->manager())->getJson($this->url('data', ['search' => 'Anak']))->assertOk();

        $this->assertSame('&lt;b&gt;Induk&lt;/b&gt;', $response->json('data.0.parent.name'));
    }

    public function test_a_unit_cannot_be_its_own_parent_or_sit_under_its_descendant(): void
    {
        $top = $this->createRecord(['name' => 'Atas']);
        $middle = $this->createRecord(['name' => 'Tengah', 'parent_id' => $top->id]);
        $bottom = $this->createRecord(['name' => 'Bawah', 'parent_id' => $middle->id]);
        $manager = $this->manager();

        foreach ([$top, $middle, $bottom] as $forbiddenParent) {
            $this->actingAs($manager)
                ->postJson($this->url('update', [$top]), ['name' => 'Atas', 'parent_id' => $forbiddenParent->id, 'is_active' => true])
                ->assertStatus(422)
                ->assertJsonPath('errors.parent_id.0', 'Unit induk tidak boleh unit itu sendiri atau unit di bawahnya.');
        }

        $this->assertNull($top->fresh()->parent_id);
        $this->assertSame([$middle->id, $bottom->id], $top->descendantIds());
    }

    public function test_the_parent_must_belong_to_the_same_organization(): void
    {
        $foreignUnit = $this->createRecord([], Organization::factory()->create());

        $this->actingAs($this->manager())
            ->postJson($this->url('insert'), [...$this->validPayload(), 'parent_id' => $foreignUnit->id])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['parent_id']]);
    }

    public function test_parent_options_leave_out_the_unit_being_edited_and_other_organizations(): void
    {
        $editing = $this->createRecord(['name' => 'Sedang Diubah']);
        $candidate = $this->createRecord(['name' => 'Calon Induk']);
        $this->createRecord(['name' => 'Organisasi Lain'], Organization::factory()->create());

        $child = $this->createRecord(['name' => 'Anak dari yang Diubah', 'parent_id' => $editing->id]);
        $manager = $this->manager();

        $this->actingAs($manager)->getJson($this->url('options', ['except' => $editing->id]))
            ->assertOk()
            ->assertJsonCount(1, 'data.results')
            ->assertJsonPath('data.results.0.id', $candidate->id)
            ->assertJsonPath('data.results.0.text', 'Calon Induk')
            ->assertJsonPath('data.more', false);

        $this->actingAs($manager)->getJson($this->url('options', ['search' => 'anak']))
            ->assertJsonCount(1, 'data.results')
            ->assertJsonPath('data.results.0.id', $child->id);
    }

    public function test_editing_a_unit_sends_its_parents_name_for_the_remote_dropdown(): void
    {
        $parent = $this->createRecord(['name' => 'Bidang Pembinaan']);
        $child = $this->createRecord(['name' => 'Komisi Anak', 'parent_id' => $parent->id]);

        $this->actingAs($this->manager())->getJson($this->url('show', [$child]))
            ->assertJsonPath('data.parent_id', $parent->id)
            ->assertJsonPath('data.parent_id_label', 'Bidang Pembinaan');
    }

    public function test_a_unit_with_users_cannot_be_deleted(): void
    {
        $unit = $this->createRecord();
        User::factory()->create(['organization_unit_id' => $unit->id]);

        $this->actingAs($this->manager())->postJson($this->url('delete', [$unit]))
            ->assertStatus(409)
            ->assertJsonPath('status', false)
            ->assertJsonPath('data', 'Data ini sudah dipakai oleh data lain sehingga tidak dapat dihapus. Nonaktifkan saja bila tidak digunakan lagi.');

        $this->assertModelExists($unit);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'organization_units.deleted']);
    }

    public function test_a_unit_with_child_units_cannot_be_deleted(): void
    {
        $parent = $this->createRecord();
        $this->createRecord(['parent_id' => $parent->id]);

        $this->actingAs($this->manager())->postJson($this->url('delete', [$parent]))->assertStatus(409);

        $this->assertModelExists($parent);
    }
}
