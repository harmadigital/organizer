<?php

namespace Tests\Feature\Events;

use App\Enums\CommitteeRoleKind;
use App\Models\OrganizationUnit;
use App\Models\User;
use App\Services\PeopleLookup;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\TestCase;

class PeopleLookupTest extends TestCase
{
    use BuildsCommittees;
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCommittees();
    }

    public function test_it_finds_active_people_by_name_username_or_phone_in_select2_shape(): void
    {
        $unit = OrganizationUnit::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Komisi Pemuda']);
        $debora = User::factory()->create(['name' => 'Debora Simanjuntak', 'username' => 'debora', 'organization_unit_id' => $unit->id]);
        User::factory()->withoutLogin()->create(['name' => 'Ruth Hutapea', 'phone' => '081277778888']);
        User::factory()->inactive()->create(['name' => 'Debora Nonaktif']);

        $this->actingAs($this->organizer)->getJson(route('events.people_options', ['search' => 'debora']))
            ->assertOk()
            ->assertJsonCount(1, 'data.results')
            ->assertJsonPath('data.results.0', [
                'id' => $debora->id,
                'text' => 'Debora Simanjuntak',
                'description' => 'Komisi Pemuda',
                'has_login' => true,
            ])
            ->assertJsonPath('data.more', false);

        $this->actingAs($this->organizer)->getJson(route('events.people_options', ['search' => '7777']))
            ->assertJsonPath('data.results.0.text', 'Ruth Hutapea')
            ->assertJsonPath('data.results.0.description', 'Tanpa akses login')
            ->assertJsonPath('data.results.0.has_login', false);
    }

    public function test_results_are_paged(): void
    {
        User::factory()->count(PeopleLookup::PER_PAGE + 5)->create();

        $first = $this->actingAs($this->organizer)->getJson(route('events.people_options', ['page' => 1]))->assertOk();
        $second = $this->actingAs($this->organizer)->getJson(route('events.people_options', ['page' => 2]))->assertOk();

        $this->assertCount(PeopleLookup::PER_PAGE, $first->json('data.results'));
        $this->assertTrue($first->json('data.more'));
        $this->assertFalse($second->json('data.more'));
        $this->assertSame([], array_intersect($first->json('data.results.*.id'), $second->json('data.results.*.id')));
    }

    public function test_the_organization_wide_lookup_needs_an_event_permission(): void
    {
        $this->actingAs(User::factory()->create())->getJson(route('events.people_options'))->assertForbidden();
        $this->actingAs($this->userWithPermissions('events.view'))->getJson(route('events.people_options'))->assertForbidden();
        $this->actingAs($this->userWithPermissions('events.create'))->getJson(route('events.people_options'))->assertOk();
    }

    public function test_inside_an_event_only_people_who_edit_it_or_appoint_members_may_search(): void
    {
        $event = $this->makeEvent();
        $division = $this->makeDivision($event);
        $chair = User::factory()->create();
        $coordinator = User::factory()->create();
        $treasurer = User::factory()->create();
        $this->seat($chair, $event, CommitteeRoleKind::Chair);
        $this->seat($coordinator, $event, CommitteeRoleKind::DivisionCoordinator, $division);
        $this->seat($treasurer, $event, CommitteeRoleKind::Treasurer);

        $this->actingAs($chair)->getJson(route('events.event_people_options', $event))->assertOk();
        $this->actingAs($coordinator)->getJson(route('events.event_people_options', $event))->assertOk();
        $this->actingAs($treasurer)->getJson(route('events.event_people_options', $event))->assertForbidden();
        $this->actingAs(User::factory()->create())->getJson(route('events.event_people_options', $event))->assertNotFound();
    }

    public function test_the_edit_form_receives_the_responsible_persons_name(): void
    {
        $responsible = User::factory()->create(['name' => 'Penanggung Jawab']);
        $event = $this->makeEvent(['responsible_user_id' => $responsible->id]);

        $this->actingAs($this->organizer)->getJson(route('events.show', $event))
            ->assertJsonPath('data.responsible_user_id', $responsible->id)
            ->assertJsonPath('data.responsible_user_id_label', 'Penanggung Jawab');
    }
}
