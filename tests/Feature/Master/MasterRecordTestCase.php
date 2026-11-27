<?php

namespace Tests\Feature\Master;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithOrganization;
use Tests\TestCase;

/**
 * Behaviour every list-and-modal master data screen must have. A concrete
 * test class describes its screen and inherits these tests.
 */
abstract class MasterRecordTestCase extends TestCase
{
    use InteractsWithOrganization;
    use LazilyRefreshDatabase;

    /**
     * Route name prefix, e.g. "master.venue".
     */
    abstract protected function routePrefix(): string;

    /**
     * Permission prefix, e.g. "venues".
     */
    abstract protected function permissionPrefix(): string;

    /**
     * Audit prefix, e.g. "venues".
     */
    abstract protected function auditSubject(): string;

    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * A payload the screen accepts for a new record.
     *
     * @return array<string, mixed>
     */
    abstract protected function validPayload(): array;

    /**
     * Column holding the record's display name.
     */
    protected function nameAttribute(): string
    {
        return 'name';
    }

    /**
     * How this screen expresses "in use" versus "retired" in a payload or factory state.
     *
     * @return array<string, mixed>
     */
    protected function activeState(bool $isActive): array
    {
        return ['is_active' => $isActive];
    }

    protected function assertRecordIsActive(bool $expected, Model $record): void
    {
        $this->assertSame($expected, $record->fresh()->is_active);
    }

    /**
     * Text only this screen shows.
     */
    abstract protected function pageHeading(): string;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpOrganization();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createRecord(array $attributes = [], ?Organization $organization = null): Model
    {
        return $this->modelClass()::factory()->create([
            'organization_id' => ($organization ?? $this->organization)->id,
            ...$attributes,
        ]);
    }

    protected function manager(): User
    {
        $prefix = $this->permissionPrefix();

        return $this->userWithPermissions("{$prefix}.view", "{$prefix}.create", "{$prefix}.update", "{$prefix}.delete");
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function url(string $action, array $parameters = []): string
    {
        return route($this->routePrefix().'.'.$action, $parameters);
    }

    public function test_the_page_follows_the_list_pattern_of_the_design_brief(): void
    {
        $response = $this->actingAs($this->manager())->get($this->url('index'));

        $response->assertOk();
        $response->assertSee($this->pageHeading());
        $response->assertSeeInOrder(['ui-page-header', 'ui-panel', 'ui-toolbar', 'kt_datatable_master', 'kt_modal_master', 'kt_modal_guide'], false);
        $response->assertSee('data-master-add', false);
        $response->assertSee('data-can-update="1"', false);
        $response->assertSee('data-can-delete="1"', false);
        $response->assertSee('assets/js/organizer/master-crud.js', false);
    }

    public function test_a_viewer_gets_a_read_only_page(): void
    {
        $viewer = $this->userWithPermissions($this->permissionPrefix().'.view');

        $this->actingAs($viewer)->get($this->url('index'))
            ->assertOk()
            ->assertDontSee('data-master-add', false)
            ->assertSee('data-can-update="0"', false)
            ->assertSee('data-can-delete="0"', false);
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get($this->url('index'))->assertRedirectToRoute('login');
        $this->postJson($this->url('insert'), $this->validPayload())->assertUnauthorized();
    }

    public function test_a_user_without_permission_cannot_open_the_screen(): void
    {
        $record = $this->createRecord();
        $outsider = $this->userWithPermissions('dashboard.view');

        $this->actingAs($outsider)->get($this->url('index'))->assertForbidden();
        $this->actingAs($outsider)->getJson($this->url('data'))->assertForbidden();
        $this->actingAs($outsider)->getJson($this->url('show', [$record]))->assertForbidden();
    }

    public function test_a_viewer_cannot_change_anything(): void
    {
        $record = $this->createRecord();
        $viewer = $this->userWithPermissions($this->permissionPrefix().'.view');

        $this->actingAs($viewer)->postJson($this->url('insert'), $this->validPayload())->assertForbidden();
        $this->actingAs($viewer)->postJson($this->url('update', [$record]), $this->validPayload())->assertForbidden();
        $this->actingAs($viewer)->postJson($this->url('delete', [$record]))->assertForbidden();

        $this->assertSame(1, $this->modelClass()::query()->count());
        $this->assertModelExists($record);
    }

    public function test_the_screen_is_unavailable_until_the_organization_profile_exists(): void
    {
        Organization::query()->delete();
        $manager = $this->manager();

        $this->actingAs($manager)->get($this->url('index'))->assertForbidden();
        $this->actingAs($manager)->postJson($this->url('insert'), $this->validPayload())
            ->assertStatus(409)
            ->assertJsonPath('status', false);

        $this->assertSame(0, $this->modelClass()::query()->count());
    }

    public function test_the_list_shows_only_records_of_the_current_organization(): void
    {
        $mine = $this->createRecord();
        $this->createRecord([], Organization::factory()->create());

        $this->actingAs($this->manager())->getJson($this->url('data'))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.id', $mine->id);
    }

    public function test_the_list_can_be_searched_by_name(): void
    {
        $this->createRecord([$this->nameAttribute() => 'Gedung Serbaguna Betlehem']);
        $this->createRecord([$this->nameAttribute() => 'Aula Efrata']);

        $this->actingAs($this->manager())->getJson($this->url('data', ['search' => 'betlehem']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.'.$this->nameAttribute(), 'Gedung Serbaguna Betlehem');
    }

    public function test_search_treats_wildcard_characters_literally(): void
    {
        $this->createRecord([$this->nameAttribute() => 'Diskon 100% Panitia']);
        $this->createRecord([$this->nameAttribute() => 'Diskon 1000 Panitia']);

        $this->actingAs($this->manager())->getJson($this->url('data', ['search' => '100%']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);
    }

    public function test_the_list_can_be_filtered_by_status(): void
    {
        $active = $this->createRecord($this->activeState(true));
        $inactive = $this->createRecord($this->activeState(false));

        $this->actingAs($manager = $this->manager())->getJson($this->url('data', ['status' => 'inactive']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.id', $inactive->id);

        $this->actingAs($manager)->getJson($this->url('data', ['status' => 'active']))
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.id', $active->id);
    }

    public function test_the_list_escapes_html_in_stored_text(): void
    {
        $this->createRecord([$this->nameAttribute() => '<script>alert(1)</script>']);

        $response = $this->actingAs($this->manager())->getJson($this->url('data'))->assertOk();

        $this->assertStringNotContainsString('<script>', $response->getContent());
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $response->json('data.0.'.$this->nameAttribute()));
    }

    public function test_a_record_can_be_created(): void
    {
        $manager = $this->manager();
        $payload = $this->validPayload();

        $response = $this->actingAs($manager)->postJson($this->url('insert'), $payload);

        $response->assertCreated()->assertJsonPath('status', true);

        $record = $this->modelClass()::query()->sole();
        $this->assertSame($this->organization->id, $record->organization_id);
        $this->assertSame($payload[$this->nameAttribute()], $record->getAttribute($this->nameAttribute()));
        $this->assertSame($manager->id, $record->created_by);
        $this->assertRecordIsActive(true, $record);
    }

    public function test_creating_a_record_is_audited(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->postJson($this->url('insert'), $this->validPayload())->assertCreated();

        $entry = AuditLog::query()->where('action', $this->auditSubject().'.created')->sole();
        $this->assertSame($manager->id, $entry->actor_user_id);
        $this->assertSame($this->organization->id, $entry->organization_id);
        $this->assertTrue($entry->auditable->is($this->modelClass()::query()->sole()));
        $this->assertNull($entry->before);
        $this->assertArrayHasKey($this->nameAttribute(), $entry->after);
    }

    public function test_text_is_stored_as_typed_without_html_escaping(): void
    {
        $payload = [...$this->validPayload(), $this->nameAttribute() => '  Pemuda & Remaja <Pusat>  '];

        $this->actingAs($this->manager())->postJson($this->url('insert'), $payload)->assertCreated();

        $this->assertSame('Pemuda & Remaja <Pusat>', $this->modelClass()::query()->sole()->getAttribute($this->nameAttribute()));
    }

    public function test_the_name_is_required(): void
    {
        $payload = [...$this->validPayload(), $this->nameAttribute() => ''];

        $response = $this->actingAs($this->manager())->postJson($this->url('insert'), $payload);

        $response->assertStatus(422)->assertJsonPath('status', false);
        $this->assertStringContainsString('wajib diisi', $response->json('errors.'.$this->nameAttribute().'.0'));
        $this->assertSame($response->json('errors.'.$this->nameAttribute().'.0'), $response->json('data'));
        $this->assertSame(0, $this->modelClass()::query()->count());
    }

    public function test_a_record_can_be_updated_and_the_change_is_audited(): void
    {
        $record = $this->createRecord([$this->nameAttribute() => 'Nama Lama']);
        $manager = $this->manager();
        $payload = [...$this->validPayload(), $this->nameAttribute() => 'Nama Baru'];

        $this->actingAs($manager)->postJson($this->url('update', [$record]), $payload)
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertSame('Nama Baru', $record->fresh()->getAttribute($this->nameAttribute()));
        $this->assertSame($manager->id, $record->fresh()->updated_by);

        $entry = AuditLog::query()->where('action', $this->auditSubject().'.updated')->sole();
        $this->assertSame('Nama Lama', $entry->before[$this->nameAttribute()]);
        $this->assertSame('Nama Baru', $entry->after[$this->nameAttribute()]);
    }

    public function test_a_record_can_be_retired_instead_of_deleted(): void
    {
        $record = $this->createRecord($this->activeState(true));
        $payload = [...$this->validPayload(), ...$this->activeState(false)];

        $this->actingAs($this->manager())->postJson($this->url('update', [$record]), $payload)->assertOk();

        $this->assertRecordIsActive(false, $record);
    }

    public function test_a_record_can_be_read_for_editing(): void
    {
        $record = $this->createRecord([$this->nameAttribute() => 'Ibadah & Doa <Raya>']);

        $this->actingAs($this->manager())->getJson($this->url('show', [$record]))
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.id', $record->id)
            ->assertJsonPath('data.'.$this->nameAttribute(), 'Ibadah & Doa <Raya>');
    }

    public function test_an_unused_record_can_be_deleted_and_the_deletion_is_audited(): void
    {
        $record = $this->createRecord([$this->nameAttribute() => 'Akan Dihapus']);

        $this->actingAs($this->manager())->postJson($this->url('delete', [$record]))
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertModelMissing($record);

        $entry = AuditLog::query()->where('action', $this->auditSubject().'.deleted')->sole();
        $this->assertSame($record->id, $entry->auditable_id);
        $this->assertSame('Akan Dihapus', $entry->before[$this->nameAttribute()]);
    }

    public function test_a_record_of_another_organization_cannot_be_reached(): void
    {
        $foreign = $this->createRecord([$this->nameAttribute() => 'Milik Organisasi Lain'], Organization::factory()->create());
        $manager = $this->manager();

        $this->actingAs($manager)->getJson($this->url('show', [$foreign]))->assertNotFound();
        $this->actingAs($manager)->postJson($this->url('update', [$foreign]), $this->validPayload())->assertNotFound();
        $this->actingAs($manager)->postJson($this->url('delete', [$foreign]))->assertNotFound();

        $this->assertSame('Milik Organisasi Lain', $foreign->fresh()->getAttribute($this->nameAttribute()));
    }
}
