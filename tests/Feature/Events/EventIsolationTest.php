<?php

namespace Tests\Feature\Events;

use App\Actions\Work\AddTaskDependency;
use App\Actions\Work\AttachTaskDocument;
use App\Actions\Work\PostTaskComment;
use App\Actions\Work\SaveTask;
use App\Actions\Work\SaveWorkProgram;
use App\Enums\CommitteeRoleKind;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkProgram;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Storage;
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
     * One record of every kind that lives inside an event, so every route parameter has a value.
     *
     * @return array<string, mixed>
     */
    private function recordsOf(Event $event, string $suffix): array
    {
        Storage::fake('local');

        $division = $this->makeDivision($event, 'Seksi '.$suffix);
        $assignment = $this->seat(User::factory()->create(), $event, CommitteeRoleKind::DivisionMember, $division, accepted: false);
        $program = $this->app->make(SaveWorkProgram::class)->handle($event, new WorkProgram, ['division_id' => $division->id, 'title' => 'Program '.$suffix, 'status' => 'active'], $this->organizer);
        $saveTask = $this->app->make(SaveTask::class);
        $task = $saveTask->handle($event, new Task, ['title' => 'Tugas '.$suffix, 'division_id' => $division->id, 'priority' => 'medium', 'weight' => 1], [], $this->organizer);
        $prerequisite = $saveTask->handle($event, new Task, ['title' => 'Prasyarat '.$suffix, 'priority' => 'medium', 'weight' => 1], [], $this->organizer);
        $this->app->make(AddTaskDependency::class)->handle($task, $prerequisite, $this->organizer);
        $comment = $this->app->make(PostTaskComment::class)->handle($task, $this->organizer, 'Komentar '.$suffix);
        $document = $this->app->make(AttachTaskDocument::class)->handle($task, UploadedFile::fake()->create('bukti.pdf', 10, 'application/pdf'), null, $this->organizer);

        return [
            'event' => $event,
            'division' => $division,
            'assignment' => $assignment,
            'membership' => $assignment->membership,
            'program' => $program,
            'task' => $task,
            'dependency' => $prerequisite,
            'document' => $document,
            'comment' => $comment,
        ];
    }

    /**
     * @param  array<string, mixed>  $records
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
        $this->assertGreaterThanOrEqual(37, count($this->eventRoutes()));
    }

    public function test_the_chair_of_one_event_reaches_nothing_of_another_event(): void
    {
        $this->setUpCommittees();
        $eventA = $this->makeEvent(['name' => 'Event A']);
        $eventB = $this->makeEvent(['name' => 'Event B']);
        $chairOfA = User::factory()->create();
        $this->seat($chairOfA, $eventA, CommitteeRoleKind::Chair);

        $recordsOfB = $this->recordsOf($eventB, 'B');
        $divisionB = $recordsOfB['division'];
        $assignmentB = $recordsOfB['assignment'];

        foreach ($this->eventRoutes() as $route) {
            $this->requestAs($chairOfA, $route, $this->urlFor($route, $recordsOfB))->assertNotFound();
        }

        $this->assertSame('Seksi B', $divisionB->fresh()->name);
        $this->assertTrue($assignmentB->fresh()->isActive());
        $this->assertSame('draft', $eventB->fresh()->status->value);
        $this->assertSame('todo', $recordsOfB['task']->fresh()->status->value);
        $this->assertModelExists($recordsOfB['comment']);
        $this->assertSame('Program B', $recordsOfB['program']->fresh()->title);
        $this->assertNotSoftDeleted($recordsOfB['document']);
    }

    public function test_records_of_another_event_cannot_be_reached_through_an_event_one_manages(): void
    {
        $this->setUpCommittees();
        $eventA = $this->makeEvent(['name' => 'Event A']);
        $eventB = $this->makeEvent(['name' => 'Event B']);
        $recordsOfB = $this->recordsOf($eventB, 'B');
        $divisionB = $recordsOfB['division'];
        $assignmentB = $recordsOfB['assignment'];

        $childRoutes = array_filter($this->eventRoutes(), fn (Route $route) => count($route->parameterNames()) > 1);
        $mixed = ['event' => $eventA, ...array_diff_key($recordsOfB, ['event' => true])];

        foreach ($childRoutes as $route) {
            $this->requestAs($this->organizer, $route, $this->urlFor($route, $mixed))->assertNotFound();
        }

        $this->assertSame('Seksi B', $divisionB->fresh()->name);
        $this->assertTrue($assignmentB->fresh()->isActive());
        $this->assertSame('todo', $recordsOfB['task']->fresh()->status->value);
    }

    /**
     * Prerequisites, files and comments resolve through their task, so a task one manages
     * cannot reach those of another task, even in the same event.
     */
    public function test_records_of_another_task_cannot_be_reached_through_a_task_one_manages(): void
    {
        $this->setUpCommittees();
        $event = $this->makeEvent();
        $records = $this->recordsOf($event, 'A');
        $otherTask = $this->app->make(SaveTask::class)->handle($event, new Task, ['title' => 'Tugas lain', 'priority' => 'medium', 'weight' => 1], [], $this->organizer);

        $taskChildRoutes = array_filter(
            $this->eventRoutes(),
            fn (Route $route) => in_array('task', $route->parameterNames(), true) && count($route->parameterNames()) > 2,
        );
        $this->assertCount(4, $taskChildRoutes);

        foreach ($taskChildRoutes as $route) {
            $this->requestAs($this->organizer, $route, $this->urlFor($route, ['task' => $otherTask, ...array_diff_key($records, ['task' => true])]))->assertNotFound();
        }

        $this->assertSame(1, $records['task']->dependencies()->count());
        $this->assertSame(1, $records['task']->documents()->count());
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
