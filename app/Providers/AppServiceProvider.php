<?php

namespace App\Providers;

use App\Enums\EventStatus;
use App\Enums\MembershipStatus;
use App\Models\CommitteeRole;
use App\Models\Division;
use App\Models\DivisionTemplate;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventMembership;
use App\Models\EventRoleAssignment;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\User;
use App\Models\Venue;
use App\Services\CurrentOrganization;
use App\Services\DisplayTime;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\WipeCommand;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentOrganization::class);

        $this->app->bind(DisplayTime::class, fn (): DisplayTime => new DisplayTime(
            $this->app->make(CurrentOrganization::class)->timezone(),
            config('app.locale'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->prohibitDataLossCommands();
        $this->restrictPolymorphicTypes();
        $this->shareEventSwitcher();
    }

    /**
     * Events the signed-in person serves in, for the switcher in the header.
     */
    private function shareEventSwitcher(): void
    {
        View::composer('layouts.partials.event-switcher', function ($view): void {
            $user = Auth::user();
            $organizationId = $this->app->make(CurrentOrganization::class)->id();

            $view->with('switcherEvents', $user === null || $organizationId === null ? collect() : Event::query()
                ->forOrganization($organizationId)
                ->whereIn('status', [EventStatus::Draft, EventStatus::Active])
                ->whereHas('memberships', fn ($memberships) => $memberships
                    ->where('user_id', $user->getKey())
                    ->where('status', MembershipStatus::Active))
                ->orderBy('starts_on')
                ->limit(8)
                ->get(['id', 'organization_id', 'code', 'name', 'status']));
        });
    }

    /**
     * Refuse the commands that drop tables when running in production.
     */
    private function prohibitDataLossCommands(): void
    {
        $isProduction = $this->app->isProduction();

        FreshCommand::prohibit($isProduction);
        RefreshCommand::prohibit($isProduction);
        ResetCommand::prohibit($isProduction);
        WipeCommand::prohibit($isProduction);
    }

    /**
     * Only models listed here may be the target of a polymorphic relation.
     *
     * User keeps its class name as the alias because existing role, permission
     * and token rows already store it that way. New models get a short alias.
     */
    private function restrictPolymorphicTypes(): void
    {
        Relation::enforceMorphMap([
            User::class => User::class,
            'organization' => Organization::class,
            'organization_unit' => OrganizationUnit::class,
            'committee_role' => CommitteeRole::class,
            'division_template' => DivisionTemplate::class,
            'event_category' => EventCategory::class,
            'venue' => Venue::class,
            'role' => Role::class,
            'event' => Event::class,
            'division' => Division::class,
            'event_membership' => EventMembership::class,
            'event_role_assignment' => EventRoleAssignment::class,
        ]);
    }
}
