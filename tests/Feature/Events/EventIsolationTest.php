<?php

namespace Tests\Feature\Events;

use App\Enums\CommitteeRoleKind;
use App\Models\Division;
use App\Models\Event;
use App\Models\EventMembership;
use App\Models\EventRoleAssignment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\Concerns\BuildsCommittees;
use Tests\TestCase;

/**
 * AC-01: nobody reaches another event's data, whatever route they use. Every route
 * with an {event} parameter is covered automatically, including routes added later.
 */
class EventIsolationTest extends TestCase
{
    use BuildsCommittees;
    use LazilyRefreshDatabase;

    /**
     * @return list<Route>
     */
    private function eventRoutes(): array
    {
        return collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $route) => in_array('event', $route->parameterNames(), true) && ! str_contains($route->uri(), 'invitation'))
            ->values()
            ->all();
    }

    /**
     * @param  array{event: Event, division: Division, assignment: EventRoleAssignment, membership: EventMembership}  $records
     */
    private function urlFor(Route $route, array $records): string
    {
        $parameters = [];

        foreach ($route->parameterNames() as $name) {
            $parameters[$name] = $records[$name];
        }

        return route($route->getName(), $parameters);
    }

    private function requestAs(User $user, Route $route, string $url)
    {
        $method = in_array('POST', $route->methods(), true) ? 'postJson' : 'getJson';

        return $this->actingAs($user)->{$method}($url, ['name' => 'Coba', 'reason' => 'Coba', 'status' => 'cancelled']);
    }

    public function test_every_event_route_is_covered(): void
    {
        $this->assertGreaterThanOrEqual(17, count($this->eventRoutes()));
    }

    public function test_the_chair_of_one_event_reaches_nothing_of_another_event(): void
    {
        $this->setUpCommittees();
        $eventA = $this->makeEvent(['name' => 'Event A']);
        $eventB = $this->makeEvent(['name' => 'Event B']);
        $chairOfA = User::factory()->create();
        $this->seat($chairOfA, $eventA, CommitteeRoleKind::Chair);

        $divisionB = $this->makeDivision($eventB, 'Seksi B');
        $assignmentB = $this->seat(User::factory()->create(), $eventB, CommitteeRoleKind::DivisionMember, $divisionB, accepted: false);
        $recordsOfB = ['event' => $eventB, 'division' => $divisionB, 'assignment' => $assignmentB, 'membership' => $assignmentB->membership];

        foreach ($this->eventRoutes() as $route) {
            $this->requestAs($chairOfA, $route, $this->urlFor($route, $recordsOfB))->assertNotFound();
        }

        $this->assertSame('Seksi B', $divisionB->fresh()->name);
        $this->assertTrue($assignmentB->fresh()->isActive());
        $this->assertSame('draft', $eventB->fresh()->status->value);
    }

    public function test_records_of_another_event_cannot_be_reached_through_an_event_one_manages(): void
    {
        $this->setUpCommittees();
        $eventA = $this->makeEvent(['name' => 'Event A']);
        $eventB = $this->makeEvent(['name' => 'Event B']);
        $divisionB = $this->makeDivision($eventB, 'Seksi B');
        $assignmentB = $this->seat(User::factory()->create(), $eventB, CommitteeRoleKind::DivisionMember, $divisionB, accepted: false);

        $childRoutes = array_filter($this->eventRoutes(), fn (Route $route) => count($route->parameterNames()) > 1);
        $mixed = ['event' => $eventA, 'division' => $divisionB, 'assignment' => $assignmentB, 'membership' => $assignmentB->membership];

        foreach ($childRoutes as $route) {
            $this->requestAs($this->organizer, $route, $this->urlFor($route, $mixed))->assertNotFound();
        }

        $this->assertSame('Seksi B', $divisionB->fresh()->name);
        $this->assertTrue($assignmentB->fresh()->isActive());
    }

    public function test_an_event_of_another_organization_is_never_found(): void
    {
        $this->setUpCommittees();
        $foreign = Event::factory()->create(['organization_id' => Organization::factory()->create()->id]);
        $superAdmin = User::factory()->create()->assignRole('Super Admin');

        $this->actingAs($superAdmin)->get(route('events.overview', $foreign))->assertNotFound();
        $this->actingAs($superAdmin)->postJson(route('events.status', $foreign), ['status' => 'cancelled', 'reason' => 'x'])->assertNotFound();
    }
}
