<?php

namespace Tests\Feature\Events;

use App\Enums\CommitteeRoleKind;
use App\Enums\EventPhase;
use App\Enums\EventStatus;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organization;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use BuildsCommittees;
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCommittees();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Perayaan Natal 2026',
            'theme' => 'Damai di Bumi',
            'starts_on' => '2026-12-24',
            'ends_on' => '2026-12-25',
            'target_participants' => '1.200',
            'budget_target_amount' => 'Rp 75.000.000',
            'objectives' => [
                ['type' => 'goal', 'description' => 'Merayakan kelahiran Kristus bersama jemaat.', 'target' => ''],
                ['type' => 'indicator', 'description' => 'Kehadiran jemaat', 'target' => '1.000 orang'],
                ['type' => 'target', 'description' => '', 'target' => ''],
            ],
            ...$overrides,
        ];
    }

    public function test_an_organizer_creates_an_event_as_a_draft_with_its_own_role_rules(): void
    {
        $this->actingAs($this->organizer)->postJson(route('events.insert'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('status', true);

        $event = Event::query()->sole();
        $this->assertSame(EventStatus::Draft, $event->status);
        $this->assertSame(EventPhase::Initiation, $event->phase);
        $this->assertSame('EVT-2026-001', $event->code);
        $this->assertSame(1200, $event->target_participants);
        $this->assertSame(75000000, $event->budget_target_amount);
        $this->assertSame('IDR', $event->currency_code);
        $this->assertSame(2, $event->objectives()->count());
        $this->assertSame(7, $event->roleRules()->count());
        $this->assertEqualsCanonicalizing(
            ['Ketua', 'Sekretaris', 'Bendahara', 'Komite / Pengawas'],
            $event->roleRules()->where('is_required', true)->with('committeeRole')->get()->pluck('committeeRole.name')->all(),
        );
        $this->assertSame(1, $event->statusHistories()->count());
        $this->assertTrue(AuditLog::query()->where('action', 'events.created')->where('event_id', $event->id)->exists());
    }

    public function test_codes_follow_the_year_of_the_event_and_a_custom_code_is_accepted(): void
    {
        $this->makeEvent();
        $this->makeEvent(['name' => 'Kedua']);

        $this->actingAs($this->organizer)->postJson(route('events.insert'), $this->payload(['code' => 'natal-26']))->assertCreated();
        $this->actingAs($this->organizer)->postJson(route('events.insert'), $this->payload(['code' => 'NATAL-26']))
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'Kode event ini sudah dipakai event lain.');

        $this->assertEqualsCanonicalizing(['EVT-2026-001', 'EVT-2026-002', 'NATAL-26'], Event::query()->pluck('code')->all());
    }

    public function test_changing_the_role_defaults_later_does_not_touch_an_existing_event(): void
    {
        $event = $this->makeEvent();
        $this->role(CommitteeRoleKind::Deputy)->update(['is_required_default' => true]);

        $this->assertFalse($event->roleRules()->where('committee_role_id', $this->role(CommitteeRoleKind::Deputy)->id)->value('is_required'));
    }

    public function test_dates_and_references_are_validated(): void
    {
        $foreignVenue = Venue::factory()->create(['organization_id' => Organization::factory()->create()->id]);

        $this->actingAs($this->organizer)->postJson(route('events.insert'), $this->payload([
            'name' => '',
            'ends_on' => '2026-12-20',
            'venue_id' => $foreignVenue->id,
            'committee_starts_on' => '2027-01-01',
            'objectives' => [['type' => 'wish', 'description' => 'x']],
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Kolom nama event wajib diisi.')
            ->assertJsonPath('errors.ends_on.0', 'Tanggal selesai tidak boleh sebelum tanggal mulai.')
            ->assertJsonStructure(['errors' => ['venue_id', 'committee_starts_on', 'objectives.0.type']]);

        $this->assertSame(0, Event::query()->count());
    }

    public function test_only_people_allowed_to_create_events_can(): void
    {
        $this->actingAs(User::factory()->create())->postJson(route('events.insert'), $this->payload())->assertForbidden();
        $this->actingAs($this->userWithPermissions('events.view'))->postJson(route('events.insert'), $this->payload())->assertForbidden();

        $this->assertSame(0, Event::query()->count());
    }

    public function test_the_chair_and_the_organization_can_edit_but_other_members_cannot(): void
    {
        $event = $this->makeEvent();
        $category = EventCategory::factory()->create(['organization_id' => $this->organization->id]);
        $chair = User::factory()->create();
        $secretary = User::factory()->create();
        $this->seat($chair, $event, CommitteeRoleKind::Chair);
        $this->seat($secretary, $event, CommitteeRoleKind::Secretary);

        $this->actingAs($secretary)->postJson(route('events.update', $event), $this->payload())->assertForbidden();
        $this->actingAs($chair)->postJson(route('events.update', $event), $this->payload(['event_category_id' => $category->id, 'objectives' => []]))->assertOk();

        $event->refresh();
        $this->assertSame($category->id, $event->event_category_id);
        $this->assertSame(0, $event->objectives()->count());

        $entry = AuditLog::query()->where('action', 'events.updated')->sole();
        $this->assertSame($chair->id, $entry->actor_user_id);
        $this->assertSame($category->id, $entry->after['event_category_id']);
        $this->assertArrayNotHasKey('objectives', $entry->after);

        $this->actingAs($this->organizer)->getJson(route('events.show', $event))
            ->assertOk()
            ->assertJsonPath('data.starts_on', '2026-12-24')
            ->assertJsonPath('data.code', $event->code);
    }

    public function test_a_closed_event_can_no_longer_be_edited(): void
    {
        $event = Event::factory()->withStatus(EventStatus::Completed)->create(['organization_id' => $this->organization->id]);

        $this->actingAs($this->organizer)->postJson(route('events.update', $event), $this->payload())->assertForbidden();
        $this->actingAs($this->organizer)->get(route('events.overview', $event))
            ->assertOk()
            ->assertSee('sehingga data dan susunan panitianya tidak dapat diubah.')
            ->assertDontSee('data-event-edit', false);
    }

    public function test_the_event_form_is_a_five_step_stepper_with_flatpickr_dates(): void
    {
        $html = $this->actingAs($this->organizer)->get(route('events.index'))->assertOk()->getContent();

        $this->assertSame(5, substr_count($html, 'data-kt-stepper-element="nav"'));
        $this->assertSame(5, substr_count($html, 'data-kt-stepper-element="content"'));
        $this->assertStringContainsString('id="kt_event_stepper"', $html);
        $this->assertSame(4, substr_count($html, 'data-date-picker'));
        $this->assertStringContainsString('data-date-min="#event_starts_on"', $html);
        $this->assertStringContainsString('data-date-min="#event_committee_starts_on"', $html);
        $this->assertStringNotContainsString('type="date"', $html);
        $this->assertSame(3, substr_count($html, 'data-step-required'));
    }

    public function test_the_list_page_shows_invitations_and_the_events_one_serves_in(): void
    {
        $christmas = $this->makeEvent(['name' => 'Perayaan Natal']);
        $retreat = $this->makeEvent(['name' => 'Retret Pemuda', 'starts_on' => '2027-02-01', 'ends_on' => '2027-02-03']);
        $this->makeEvent(['name' => 'Bakti Sosial Rahasia']);
        $person = User::factory()->create();
        $this->seat($person, $christmas, CommitteeRoleKind::Secretary);
        $this->seat($person, $retreat, CommitteeRoleKind::Treasurer, accepted: false);

        $response = $this->actingAs($person)->get(route('events.index'))->assertOk();

        $response->assertSee('Undangan Kepanitiaan')
            ->assertSeeInOrder(['Undangan Kepanitiaan', 'Retret Pemuda', 'Bendahara', 'Event Saya', 'Perayaan Natal', 'Sekretaris'])
            ->assertDontSee('Bakti Sosial Rahasia')
            ->assertDontSee('kt_datatable_events', false)
            ->assertDontSee('data-event-create', false);

        $this->actingAs($person)->getJson(route('events.data'))->assertForbidden();
    }

    public function test_organization_viewers_list_every_event_with_filters(): void
    {
        $this->makeEvent(['name' => 'Perayaan Natal']);
        $retreat = $this->makeEvent(['name' => 'Retret Pemuda', 'starts_on' => '2027-02-01', 'ends_on' => '2027-02-03']);
        Event::factory()->create(['organization_id' => Organization::factory()->create()->id, 'name' => 'Organisasi Lain']);
        $viewer = $this->userWithPermissions('events.view');

        $this->actingAs($viewer)->get(route('events.index'))->assertOk()->assertSee('kt_datatable_events', false);

        $this->actingAs($viewer)->getJson(route('events.data'))->assertOk()->assertJsonPath('recordsTotal', 2);
        $this->actingAs($viewer)->getJson(route('events.data', ['year' => 2027]))
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.id', $retreat->id)
            ->assertJsonPath('data.0.status_label', 'Draf')
            ->assertJsonPath('data.0.url', route('events.overview', $retreat));
        $this->actingAs($viewer)->getJson(route('events.data', ['search' => 'retret']))->assertJsonPath('recordsFiltered', 1);
    }

    public function test_the_overview_shows_profile_objectives_readiness_and_history(): void
    {
        $this->actingAs($this->organizer)->postJson(route('events.insert'), $this->payload())->assertCreated();
        $event = Event::query()->sole();
        $this->seat(User::factory()->create(), $event, CommitteeRoleKind::Chair);

        $this->actingAs($this->organizer)->get(route('events.overview', $event))
            ->assertOk()
            ->assertSee('Damai di Bumi')
            ->assertSee('Rp 75.000.000')
            ->assertSee('Target: 1.000 orang')
            ->assertSee('Jabatan Wajib')
            ->assertSee('data-required-role="Ketua" data-filled="1"', false)
            ->assertSee('data-required-role="Bendahara" data-filled="0"', false)
            ->assertSee('1/4')
            ->assertSee('Event dibuat sebagai Draf')
            ->assertSee('data-status-target="active"', false)
            ->assertSeeInOrder(['ui-page-header', 'ui-subnav', 'ui-stat-card'], false);
    }

    public function test_inside_an_event_the_sidebar_only_highlights_event_and_the_switcher_shows_it(): void
    {
        $event = $this->makeEvent(['name' => 'Perayaan Natal']);
        $chair = User::factory()->create();
        $this->seat($chair, $event, CommitteeRoleKind::Chair);

        $response = $this->actingAs($chair)->get(route('events.committee', $event))->assertOk();
        $sidebar = str($response->getContent())->between('id="#kt_app_sidebar_menu"', 'id="kt_app_sidebar_footer"');

        $this->assertFalse($sidebar->contains(route('events.committee', $event)));
        $this->assertFalse($sidebar->contains(route('events.overview', $event)));
        $this->assertTrue($sidebar->contains('class="menu-link active" href="'.route('events.index').'"'));
        $response->assertSee('data-event-switcher', false)
            ->assertSee(route('events.overview', $event), false);
    }
}
