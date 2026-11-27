<?php

namespace Tests\Feature\Events;

use App\Enums\CommitteeRoleKind;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\DivisionTemplate;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\TestCase;

class DivisionTest extends TestCase
{
    use BuildsCommittees;
    use LazilyRefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCommittees();
        $this->event = $this->makeEvent();
    }

    public function test_a_division_can_be_created_under_another_and_is_audited(): void
    {
        $program = $this->makeDivision($this->event, 'Seksi Acara');

        $this->actingAs($this->organizer)->postJson(route('events.divisions.insert', $this->event), [
            'name' => 'Sub-seksi Musik',
            'parent_id' => $program->id,
            'responsibilities' => "Latihan paduan suara\nMenyiapkan pemusik",
        ])->assertCreated();

        $music = Division::query()->where('name', 'Sub-seksi Musik')->sole();
        $this->assertTrue($music->parent->is($program));
        $this->assertSame("Latihan paduan suara\nMenyiapkan pemusik", $music->responsibilities);
        $this->assertTrue(AuditLog::query()->where('action', 'divisions.created')->where('auditable_id', $music->id)->exists());
    }

    public function test_names_are_unique_within_an_event_only(): void
    {
        $this->makeDivision($this->event, 'Seksi Acara');
        $this->makeDivision($this->makeEvent(['name' => 'Retret']), 'Seksi Konsumsi');

        $this->actingAs($this->organizer)->postJson(route('events.divisions.insert', $this->event), ['name' => 'Seksi Acara'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Nama seksi ini sudah dipakai pada event ini.');
        $this->actingAs($this->organizer)->postJson(route('events.divisions.insert', $this->event), ['name' => 'Seksi Konsumsi'])->assertCreated();
    }

    public function test_a_parent_must_be_in_the_same_event_and_cycles_are_refused(): void
    {
        $top = $this->makeDivision($this->event, 'Atas');
        $middle = $this->makeDivision($this->event, 'Tengah', $top);
        $bottom = $this->makeDivision($this->event, 'Bawah', $middle);
        $foreign = $this->makeDivision($this->makeEvent(['name' => 'Lain']), 'Seksi Lain');

        foreach ([$top, $middle, $bottom] as $forbiddenParent) {
            $this->actingAs($this->organizer)->postJson(route('events.divisions.update', [$this->event, $top]), ['name' => 'Atas', 'parent_id' => $forbiddenParent->id])
                ->assertStatus(422)
                ->assertJsonPath('errors.parent_id.0', 'Seksi induk tidak boleh seksi itu sendiri atau seksi di bawahnya.');
        }

        $this->actingAs($this->organizer)->postJson(route('events.divisions.insert', $this->event), ['name' => 'Anak', 'parent_id' => $foreign->id])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['parent_id']]);
    }

    public function test_divisions_are_created_from_templates_skipping_names_already_used(): void
    {
        $this->makeDivision($this->event, 'Seksi Acara');
        $program = DivisionTemplate::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Seksi Acara']);
        $catering = DivisionTemplate::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Seksi Konsumsi', 'default_responsibilities' => 'Menu dan porsi']);
        $foreign = DivisionTemplate::factory()->create(['name' => 'Seksi Asing']);

        $this->actingAs($this->organizer)->postJson(route('events.divisions.from_templates', $this->event), ['template_ids' => [$foreign->id]])
            ->assertStatus(422);

        $this->actingAs($this->organizer)->postJson(route('events.divisions.from_templates', $this->event), ['template_ids' => [$program->id, $catering->id]])
            ->assertCreated()
            ->assertJsonPath('data.created', ['Seksi Konsumsi']);

        $created = Division::query()->where('name', 'Seksi Konsumsi')->sole();
        $this->assertSame('Menu dan porsi', $created->responsibilities);
        $this->assertSame($catering->id, $created->division_template_id);
        $this->assertSame(2, $this->event->divisions()->count());
    }

    public function test_a_division_with_history_or_children_cannot_be_deleted(): void
    {
        $parent = $this->makeDivision($this->event, 'Induk');
        $this->makeDivision($this->event, 'Anak', $parent);
        $withHistory = $this->makeDivision($this->event, 'Pernah Dipakai');
        $assignment = $this->seat(User::factory()->create(), $this->event, CommitteeRoleKind::DivisionMember, $withHistory);
        $assignment->forceFill(['ends_at' => now()])->save();
        $empty = $this->makeDivision($this->event, 'Kosong');

        $this->actingAs($this->organizer)->postJson(route('events.divisions.delete', [$this->event, $parent]))
            ->assertStatus(422)
            ->assertJsonPath('data', 'Seksi ini masih memiliki sub-seksi. Pindahkan atau hapus sub-seksinya terlebih dahulu.');
        $this->actingAs($this->organizer)->postJson(route('events.divisions.delete', [$this->event, $withHistory]))->assertStatus(422);
        $this->actingAs($this->organizer)->postJson(route('events.divisions.delete', [$this->event, $empty]))->assertOk();

        $this->assertModelMissing($empty);
        $this->assertModelExists($withHistory);
    }

    public function test_only_committee_managers_handle_divisions(): void
    {
        $division = $this->makeDivision($this->event);
        $treasurer = User::factory()->create();
        $chair = User::factory()->create();
        $this->seat($treasurer, $this->event, CommitteeRoleKind::Treasurer);
        $this->seat($chair, $this->event, CommitteeRoleKind::Chair);

        $this->actingAs($treasurer)->postJson(route('events.divisions.insert', $this->event), ['name' => 'Baru'])->assertForbidden();
        $this->actingAs($treasurer)->postJson(route('events.divisions.delete', [$this->event, $division]))->assertForbidden();
        $this->actingAs($chair)->postJson(route('events.divisions.insert', $this->event), ['name' => 'Baru'])->assertCreated();
        $this->actingAs($chair)->getJson(route('events.divisions.show', [$this->event, $division]))->assertOk()->assertJsonPath('data.name', $division->name);
    }
}
