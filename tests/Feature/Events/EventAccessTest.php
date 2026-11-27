<?php

namespace Tests\Feature\Events;

use App\Enums\CommitteeRoleKind;
use App\Enums\EventAbility;
use App\Models\User;
use App\Services\EventAccess;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\BuildsCommittees;
use Tests\TestCase;

class EventAccessTest extends TestCase
{
    use BuildsCommittees;
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCommittees();
    }

    private function access(): EventAccess
    {
        return $this->app->make(EventAccess::class);
    }

    public function test_someone_outside_the_committee_has_no_access(): void
    {
        $event = $this->makeEvent();

        $permissions = $this->access()->for(User::factory()->create(), $event);

        foreach (EventAbility::cases() as $ability) {
            $this->assertFalse($permissions->hasAnywhere($ability), $ability->value);
        }
    }

    public function test_a_chair_holds_the_chair_abilities_for_the_whole_event(): void
    {
        $event = $this->makeEvent();
        $chair = User::factory()->create();
        $this->seat($chair, $event, CommitteeRoleKind::Chair);

        $permissions = $this->access()->for($chair, $event);

        $this->assertTrue($permissions->has(EventAbility::EventUpdate));
        $this->assertTrue($permissions->has(EventAbility::CommitteeManage));
        $this->assertFalse($permissions->has(EventAbility::MeetingManage));
        $this->assertTrue($permissions->holdsKind(CommitteeRoleKind::Chair));
    }

    public function test_a_division_role_only_reaches_its_division_and_the_ones_below_it(): void
    {
        $event = $this->makeEvent();
        $program = $this->makeDivision($event, 'Seksi Acara');
        $music = $this->makeDivision($event, 'Sub-seksi Musik', $program);
        $catering = $this->makeDivision($event, 'Seksi Konsumsi');
        $coordinator = User::factory()->create();
        $this->seat($coordinator, $event, CommitteeRoleKind::DivisionCoordinator, $program);

        $permissions = $this->access()->for($coordinator, $event);

        $this->assertTrue($permissions->has(EventAbility::TaskManage, $program->id));
        $this->assertTrue($permissions->has(EventAbility::TaskManage, $music->id));
        $this->assertFalse($permissions->has(EventAbility::TaskManage, $catering->id));
        $this->assertFalse($permissions->has(EventAbility::TaskManage));
        $this->assertTrue($permissions->hasAnywhere(EventAbility::EventView));
        $this->assertEqualsCanonicalizing([$program->id, $music->id], $permissions->divisionsWith(EventAbility::TaskManage));
    }

    public function test_every_active_role_counts_together(): void
    {
        $event = $this->makeEvent();
        $catering = $this->makeDivision($event, 'Seksi Konsumsi');
        $person = User::factory()->create();
        $this->seat($person, $event, CommitteeRoleKind::Secretary);
        $this->seat($person, $event, CommitteeRoleKind::DivisionMember, $catering);

        $permissions = $this->access()->for($person, $event);

        $this->assertTrue($permissions->has(EventAbility::MinutesPublish));
        $this->assertTrue($permissions->has(EventAbility::IssueReport, $catering->id));
    }

    public function test_the_same_person_has_separate_rights_in_two_events(): void
    {
        $christmas = $this->makeEvent(['name' => 'Natal']);
        $retreat = $this->makeEvent(['name' => 'Retret', 'starts_on' => '2027-03-01', 'ends_on' => '2027-03-03']);
        $catering = $this->makeDivision($retreat, 'Seksi Konsumsi');
        $person = User::factory()->create();
        $this->seat($person, $christmas, CommitteeRoleKind::Chair);
        $this->seat($person, $retreat, CommitteeRoleKind::DivisionMember, $catering);

        $this->assertTrue($this->access()->for($person, $christmas)->has(EventAbility::CommitteeManage));
        $this->assertFalse($this->access()->for($person, $retreat)->has(EventAbility::CommitteeManage));
        $this->assertFalse($this->access()->for($person, $retreat)->has(EventAbility::EventUpdate));

        $this->actingAs($person)->getJson(route('events.show', $christmas))->assertOk();
        $this->actingAs($person)->getJson(route('events.show', $retreat))->assertForbidden();
        $this->actingAs($person)->get(route('events.overview', $retreat))->assertOk();
    }

    public function test_an_invitation_gives_no_access_until_it_is_accepted(): void
    {
        $event = $this->makeEvent();
        $invited = User::factory()->create();
        $this->seat($invited, $event, CommitteeRoleKind::Secretary, accepted: false);

        $this->assertFalse($this->access()->for($invited, $event)->hasAnywhere(EventAbility::EventView));
        $this->actingAs($invited)->get(route('events.overview', $event))->assertNotFound();
    }

    public function test_access_ends_when_the_person_can_no_longer_sign_in(): void
    {
        $event = $this->makeEvent();
        $secretary = User::factory()->create();
        $this->seat($secretary, $event, CommitteeRoleKind::Secretary);
        $this->assertTrue($this->access()->for($secretary, $event)->hasAnywhere(EventAbility::EventView));

        $secretary->forceFill(['password' => null])->save();
        $this->access()->forget();

        $this->assertFalse($this->access()->for($secretary->fresh(), $event)->hasAnywhere(EventAbility::EventView));
    }

    public function test_organization_permissions_reach_every_event_without_a_committee_role(): void
    {
        $event = $this->makeEvent();
        $viewer = $this->userWithPermissions('events.view');

        $permissions = $this->access()->for($viewer, $event);

        $this->assertTrue($permissions->has(EventAbility::EventView));
        $this->assertTrue($permissions->has(EventAbility::CommitteeView));
        $this->assertFalse($permissions->has(EventAbility::EventUpdate));
        $this->assertFalse($permissions->isActiveMember());
        $this->assertTrue($this->access()->for($this->organizer, $event)->has(EventAbility::CommitteeManage));
    }

    public function test_a_supervisor_can_read_but_not_run_the_event(): void
    {
        $event = $this->makeEvent();
        $supervisor = User::factory()->create();
        $this->seat($supervisor, $event, CommitteeRoleKind::Supervisor);

        $permissions = $this->access()->for($supervisor, $event);

        $this->assertTrue($permissions->has(EventAbility::CommitteeView));
        $this->assertTrue($permissions->has(EventAbility::DecisionManage));
        $this->assertFalse($permissions->has(EventAbility::EventUpdate));
        $this->assertFalse($permissions->has(EventAbility::CommitteeManage));
        $this->assertFalse($permissions->has(EventAbility::TaskManage));
    }
}
