<?php

namespace Tests\Concerns;

use App\Actions\Events\AssignCommitteeRole;
use App\Actions\Events\SaveDivision;
use App\Actions\Events\SaveEvent;
use App\Actions\Organization\ProvisionDefaultCommitteeRoles;
use App\Enums\CommitteeRoleKind;
use App\Models\CommitteeRole;
use App\Models\Division;
use App\Models\Event;
use App\Models\EventRoleAssignment;
use App\Models\User;
use Database\Seeders\PermissionSeeder;

/**
 * Builds events and committees through the application's own actions, so test data
 * obeys the same rules as real data.
 */
trait BuildsCommittees
{
    use InteractsWithOrganization;

    /**
     * Someone from the organization who may create events and appoint any committee role.
     */
    protected User $organizer;

    protected function setUpCommittees(): void
    {
        $this->setUpOrganization();
        $this->app->make(ProvisionDefaultCommitteeRoles::class)->handle($this->organization);
        $this->organizer = User::factory()->create(['name' => 'Pengurus Organisasi'])->assignRole(PermissionSeeder::ORGANIZATION_ADMIN_ROLE);
    }

    protected function role(CommitteeRoleKind $kind): CommitteeRole
    {
        return CommitteeRole::query()->forOrganization($this->organization)->where('kind', $kind)->sole();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeEvent(array $attributes = []): Event
    {
        return $this->app->make(SaveEvent::class)->handle(new Event, [
            'name' => 'Perayaan Natal 2026',
            'starts_on' => '2026-12-24',
            'ends_on' => '2026-12-25',
            ...$attributes,
        ], [], $this->organizer);
    }

    protected function makeDivision(Event $event, string $name = 'Seksi Acara', ?Division $parent = null): Division
    {
        return $this->app->make(SaveDivision::class)->handle($event, new Division, [
            'name' => $name,
            'parent_id' => $parent?->getKey(),
            'sort_order' => 0,
        ], $this->organizer);
    }

    /**
     * Give someone a committee role. By default they already accepted, so the role is in effect.
     */
    protected function seat(User $person, Event $event, CommitteeRoleKind $kind, ?Division $division = null, bool $accepted = true, bool $isPrimary = true): EventRoleAssignment
    {
        return $this->app->make(AssignCommitteeRole::class)->handle(
            $event,
            $person,
            $this->role($kind),
            $division,
            $isPrimary,
            null,
            $accepted,
            $this->organizer,
        );
    }

    /**
     * Fill every role an event needs before it can be activated.
     *
     * @return array<string, User>
     */
    protected function fillRequiredRoles(Event $event): array
    {
        $people = [];

        foreach ([CommitteeRoleKind::Chair, CommitteeRoleKind::Secretary, CommitteeRoleKind::Treasurer, CommitteeRoleKind::Supervisor] as $kind) {
            $people[$kind->value] = User::factory()->create(['name' => $kind->label().' Event']);
            $this->seat($people[$kind->value], $event, $kind);
        }

        return $people;
    }
}
