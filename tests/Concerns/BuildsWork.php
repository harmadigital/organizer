<?php

namespace Tests\Concerns;

use App\Actions\Work\SaveTask;
use App\Actions\Work\SaveWorkProgram;
use App\Models\Division;
use App\Models\Event;
use App\Models\EventMembership;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkProgram;

/**
 * Builds work programs and tasks through the application's own actions. Use with BuildsCommittees.
 */
trait BuildsWork
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<EventMembership>  $assignees
     */
    protected function makeTask(Event $event, array $attributes = [], array $assignees = []): Task
    {
        return $this->app->make(SaveTask::class)->handle($event, new Task, [
            'title' => 'Pesan sound system',
            'priority' => 'medium',
            'weight' => 1,
            ...$attributes,
        ], array_map(fn (EventMembership $membership) => $membership->getKey(), $assignees), $this->organizer);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeProgram(Division $division, array $attributes = []): WorkProgram
    {
        return $this->app->make(SaveWorkProgram::class)->handle($division->event, new WorkProgram, [
            'division_id' => $division->getKey(),
            'title' => 'Persiapan liturgi',
            'status' => 'active',
            ...$attributes,
        ], $this->organizer);
    }

    protected function membershipOf(User $person, Event $event): EventMembership
    {
        return $event->memberships()->where('user_id', $person->getKey())->sole();
    }
}
