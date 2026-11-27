<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\CommitteeRole;
use App\Models\DivisionTemplate;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\User;
use App\Models\Venue;
use App\Services\EventReadiness;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return [
            'organizations' => Organization::query()->count(),
            'units' => OrganizationUnit::query()->count(),
            'users' => User::query()->count(),
            'roles' => Role::query()->count(),
            'committee_roles' => CommitteeRole::query()->count(),
            'division_templates' => DivisionTemplate::query()->count(),
            'event_categories' => EventCategory::query()->count(),
            'venues' => Venue::query()->count(),
            'events' => Event::query()->count(),
        ];
    }

    public function test_it_fills_every_existing_module_with_sample_data(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertSame([
            'organizations' => 1,
            'units' => 7,
            'users' => 12,
            'roles' => 3,
            'committee_roles' => 7,
            'division_templates' => 10,
            'event_categories' => 6,
            'venues' => 6,
            'events' => 2,
        ], $this->counts());

        $organization = Organization::query()->sole();
        $this->assertStringContainsString('(Demo)', $organization->name);
        $this->assertSame(1, OrganizationUnit::query()->where('is_active', false)->count());
        $this->assertSame(1, EventCategory::query()->where('is_active', false)->count());
        $this->assertSame(1, Venue::query()->where('is_active', false)->count());
        $this->assertSame('Bidang Pembinaan', OrganizationUnit::query()->where('name', 'Komisi Pemuda')->sole()->parent->name);
        $this->assertSame(0, User::query()->where('email', 'not like', '%@'.DemoSeeder::EMAIL_DOMAIN)->count());
    }

    public function test_it_covers_people_with_and_without_login_and_every_status(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertSame(4, User::query()->whereNotNull('username')->whereNotNull('password')->count());
        $this->assertSame(1, User::query()->where('status', UserStatus::Inactive)->count());
        $this->assertTrue(User::query()->where('username', 'demo.admin')->sole()->hasRole('Organization Admin'));
        $this->assertTrue(User::query()->where('username', 'demo.sekretariat')->sole()->hasRole(DemoSeeder::SAMPLE_ROLE));
        $this->assertFalse(User::query()->where('name', 'Ruth Hutapea')->sole()->canSignIn());
    }

    public function test_it_adds_an_active_event_and_a_draft_that_still_misses_required_roles(): void
    {
        $this->seed(DemoSeeder::class);

        $christmas = Event::query()->where('code', 'DEMO-NATAL-2026')->sole();
        $retreat = Event::query()->where('code', 'DEMO-RETRET-2027')->sole();

        $this->assertSame(EventStatus::Active, $christmas->status);
        $this->assertSame(4, $christmas->divisions()->count());
        $this->assertSame(8, $christmas->assignments()->active()->count());
        $this->assertSame(EventStatus::Draft, $retreat->status);
        $this->assertSame(['Bendahara', 'Komite / Pengawas'], app(EventReadiness::class)->missingRequiredRoles($retreat));
        $this->assertSame(MembershipStatus::Invited, $retreat->memberships()->whereHas('user', fn ($user) => $user->where('username', 'demo.pemuda'))->sole()->status);
    }

    public function test_demo_accounts_can_sign_in_with_the_demo_password(): void
    {
        $this->seed(DemoSeeder::class);

        $this->post('/login', ['username' => 'demo.sekretariat', 'password' => DemoSeeder::DEMO_PASSWORD]);

        $this->assertAuthenticatedAs(User::query()->where('username', 'demo.sekretariat')->sole());
        $this->get(route('master.venue.index'))->assertOk();
        $this->get(route('master.role.index'))->assertForbidden();
    }

    public function test_running_it_again_adds_nothing_and_keeps_changes_made_since(): void
    {
        $this->seed(DemoSeeder::class);
        $venue = Venue::query()->where('name', 'Aula Serbaguna')->sole();
        $venue->update(['capacity' => 400]);
        $before = $this->counts();

        $this->seed(DemoSeeder::class);

        $this->assertSame($before, $this->counts());
        $this->assertSame(400, $venue->fresh()->capacity);
    }

    public function test_it_uses_an_existing_organization_without_changing_its_profile(): void
    {
        $this->seed(DatabaseSeeder::class);
        $existing = Organization::factory()->create(['name' => 'Gereja Milik Pengguna', 'city' => 'Bandung']);

        $this->seed(DemoSeeder::class);

        $this->assertSame(1, Organization::query()->count());
        $this->assertSame('Gereja Milik Pengguna', $existing->fresh()->name);
        $this->assertSame('Bandung', $existing->fresh()->city);
        $this->assertSame(6, Venue::query()->forOrganization($existing)->count());
        $this->assertSame(13, User::query()->count());
    }

    public function test_it_refuses_to_run_outside_local_and_testing(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--class' => DemoSeeder::class, '--force' => true])
            ->expectsOutputToContain('hanya boleh dibuat di lingkungan local atau testing')
            ->assertSuccessful();

        $this->assertSame(0, Organization::query()->count());
        $this->assertSame(0, User::query()->count());
    }

    public function test_the_regular_seeder_never_adds_demo_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::query()->where('email', 'like', '%@'.DemoSeeder::EMAIL_DOMAIN)->count());
        $this->assertSame(0, Venue::query()->count());
    }
}
