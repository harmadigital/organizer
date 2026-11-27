<?php

namespace App\Actions\Organization;

use App\Enums\CommitteeRoleKind as Kind;
use App\Enums\CommitteeRoleScope as Scope;
use App\Enums\EventAbility as Ability;
use App\Models\CommitteeRole;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

class ProvisionDefaultCommitteeRoles
{
    /**
     * Give an organization the built-in committee roles it does not have yet.
     *
     * These are starting points, not rules: an administrator may rename a role,
     * change its flags and abilities, or deactivate it. A role that already
     * exists is left exactly as it is, so this is safe to run again.
     */
    public function handle(Organization $organization): void
    {
        DB::transaction(function () use ($organization): void {
            foreach ($this->defaults() as $position => $default) {
                $role = CommitteeRole::query()->firstOrCreate(
                    ['organization_id' => $organization->getKey(), 'key' => $default['kind']->value],
                    [
                        'name' => $default['kind']->label(),
                        'kind' => $default['kind'],
                        'scope' => $default['scope'],
                        'is_required_default' => $default['required'],
                        'is_singleton_default' => $default['singleton'],
                        'sort_order' => ($position + 1) * 10,
                    ],
                );

                if ($role->wasRecentlyCreated) {
                    $role->abilities()->createMany(
                        array_map(fn (Ability $ability): array => ['ability' => $ability->value], $default['abilities']),
                    );
                }
            }
        });
    }

    /**
     * @return list<array{kind: Kind, scope: Scope, required: bool, singleton: bool, abilities: list<Ability>}>
     */
    private function defaults(): array
    {
        return [
            [
                'kind' => Kind::Chair, 'scope' => Scope::Event, 'required' => true, 'singleton' => true,
                'abilities' => [
                    Ability::EventView, Ability::EventUpdate, Ability::CommitteeView, Ability::CommitteeManage,
                    Ability::ProgramView, Ability::ProgramReview, Ability::ProgramApprove,
                    Ability::TaskView, Ability::TaskManage, Ability::TaskWork,
                    Ability::MeetingView, Ability::DecisionManage, Ability::CoordinationView,
                    Ability::IssueView, Ability::IssueReport, Ability::IssueManage,
                    Ability::DocumentView, Ability::AnnouncementManage,
                ],
            ],
            [
                'kind' => Kind::Deputy, 'scope' => Scope::Event, 'required' => false, 'singleton' => true,
                'abilities' => [
                    Ability::EventView, Ability::CommitteeView, Ability::ProgramView, Ability::ProgramReview,
                    Ability::TaskView, Ability::TaskManage, Ability::TaskWork, Ability::MeetingView,
                    Ability::CoordinationView, Ability::IssueView, Ability::IssueReport, Ability::IssueManage,
                    Ability::DocumentView,
                ],
            ],
            [
                'kind' => Kind::Secretary, 'scope' => Scope::Event, 'required' => true, 'singleton' => true,
                'abilities' => [
                    Ability::EventView, Ability::CommitteeView, Ability::ProgramView,
                    Ability::TaskView, Ability::TaskManage, Ability::TaskWork,
                    Ability::MeetingView, Ability::MeetingManage, Ability::MinutesPublish, Ability::DecisionManage,
                    Ability::CoordinationView, Ability::IssueView, Ability::IssueReport, Ability::IssueManage,
                    Ability::DocumentView, Ability::DocumentManage, Ability::AnnouncementManage,
                ],
            ],
            [
                'kind' => Kind::Treasurer, 'scope' => Scope::Event, 'required' => true, 'singleton' => true,
                'abilities' => [
                    Ability::EventView, Ability::CommitteeView, Ability::ProgramView,
                    Ability::TaskView, Ability::TaskWork, Ability::MeetingView, Ability::CoordinationView,
                    Ability::IssueView, Ability::IssueReport, Ability::IssueManage,
                    Ability::DocumentView, Ability::DocumentManage,
                ],
            ],
            [
                'kind' => Kind::Supervisor, 'scope' => Scope::Event, 'required' => true, 'singleton' => false,
                'abilities' => [
                    Ability::EventView, Ability::CommitteeView, Ability::ProgramView, Ability::TaskView,
                    Ability::MeetingView, Ability::DecisionManage, Ability::CoordinationView,
                    Ability::IssueView, Ability::DocumentView,
                ],
            ],
            [
                'kind' => Kind::DivisionCoordinator, 'scope' => Scope::Division, 'required' => false, 'singleton' => true,
                'abilities' => [
                    Ability::EventView, Ability::CommitteeView, Ability::DivisionManage,
                    Ability::ProgramView, Ability::ProgramManage, Ability::ProgramSubmit,
                    Ability::TaskView, Ability::TaskManage, Ability::TaskWork,
                    Ability::MeetingView, Ability::MeetingManage,
                    Ability::CoordinationView, Ability::CoordinationManage,
                    Ability::IssueView, Ability::IssueReport, Ability::IssueManage,
                    Ability::DocumentView, Ability::DocumentManage,
                ],
            ],
            [
                'kind' => Kind::DivisionMember, 'scope' => Scope::Division, 'required' => false, 'singleton' => false,
                'abilities' => [
                    Ability::EventView, Ability::CommitteeView, Ability::ProgramView,
                    Ability::TaskView, Ability::TaskWork, Ability::MeetingView, Ability::CoordinationView,
                    Ability::IssueView, Ability::IssueReport, Ability::DocumentView,
                ],
            ],
        ];
    }
}
