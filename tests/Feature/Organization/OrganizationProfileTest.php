<?php

namespace Tests\Feature\Organization;

use App\Models\AuditLog;
use App\Models\CommitteeRole;
use App\Models\Organization;
use App\Services\CurrentOrganization;
use App\Services\DisplayTime;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithOrganization;
use Tests\TestCase;

class OrganizationProfileTest extends TestCase
{
    use InteractsWithOrganization;
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Gereja Kristen Kasih Karunia',
            'short_name' => 'GKKK',
            'timezone' => 'Asia/Makassar',
            'address' => 'Jl. Damai No. 7',
            'city' => 'Manado',
            'phone' => '0431 123456',
            'email' => 'sekretariat@gkkk.test',
            'website' => 'https://gkkk.test',
        ];
    }

    public function test_the_page_explains_that_the_profile_still_has_to_be_filled_in(): void
    {
        $admin = $this->userWithPermissions('organization.view', 'organization.update');

        $this->actingAs($admin)->get(route('organization.profile.index'))
            ->assertOk()
            ->assertSee('Profil organisasi belum diisi.')
            ->assertSee('data-is-new="1"', false)
            ->assertSee('Simpan Profil')
            ->assertSeeInOrder(['ui-page-header', 'ui-panel', 'assets/js/organization/profile.js'], false);
    }

    public function test_the_first_save_creates_the_organization_with_its_built_in_committee_roles(): void
    {
        $admin = $this->userWithPermissions('organization.view', 'organization.update');

        $this->actingAs($admin)->postJson(route('organization.profile.update'), $this->validPayload())
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.name', 'Gereja Kristen Kasih Karunia');

        $organization = Organization::query()->sole();
        $this->assertSame('Asia/Makassar', $organization->timezone);
        $this->assertSame('IDR', $organization->currency_code);
        $this->assertSame($admin->id, $organization->created_by);
        $this->assertSame(7, CommitteeRole::query()->forOrganization($organization)->count());

        $entry = AuditLog::query()->where('action', 'organization.created')->sole();
        $this->assertSame($organization->id, $entry->organization_id);
        $this->assertTrue($entry->auditable->is($organization));
    }

    public function test_saving_again_updates_the_same_organization(): void
    {
        $this->organization = Organization::factory()->create(['name' => 'Nama Lama', 'city' => 'Medan']);
        $admin = $this->userWithPermissions('organization.view', 'organization.update');

        $this->actingAs($admin)->postJson(route('organization.profile.update'), [...$this->validPayload(), 'city' => 'Medan'])
            ->assertOk();

        $this->assertSame(1, Organization::query()->count());
        $this->assertSame('Gereja Kristen Kasih Karunia', $this->organization->fresh()->name);
        $this->assertSame(0, CommitteeRole::query()->count());

        $entry = AuditLog::query()->where('action', 'organization.updated')->sole();
        $this->assertSame('Nama Lama', $entry->before['name']);
        $this->assertArrayNotHasKey('city', $entry->after);
    }

    public function test_the_page_shows_the_saved_profile(): void
    {
        $this->organization = Organization::factory()->create(['name' => 'GKI <Pusat> & Cabang', 'timezone' => 'Asia/Jayapura']);
        $admin = $this->userWithPermissions('organization.view', 'organization.update');

        $this->actingAs($admin)->get(route('organization.profile.index'))
            ->assertOk()
            ->assertDontSee('Profil organisasi belum diisi.')
            ->assertSee('GKI &lt;Pusat&gt; &amp; Cabang', false)
            ->assertSee('WIT (Asia/Jayapura)')
            ->assertSee('Mata Uang');
    }

    public function test_the_name_and_a_valid_timezone_are_required(): void
    {
        $admin = $this->userWithPermissions('organization.update');

        $this->actingAs($admin)
            ->postJson(route('organization.profile.update'), [...$this->validPayload(), 'name' => '', 'timezone' => 'Mars/Olympus'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Kolom nama organisasi wajib diisi.')
            ->assertJsonStructure(['errors' => ['timezone']]);

        $this->assertSame(0, Organization::query()->count());
    }

    public function test_contact_details_are_validated(): void
    {
        $admin = $this->userWithPermissions('organization.update');

        $this->actingAs($admin)
            ->postJson(route('organization.profile.update'), [...$this->validPayload(), 'email' => 'bukan-email', 'website' => 'javascript:alert(1)'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email', 'website']]);
    }

    public function test_a_viewer_sees_the_profile_read_only_and_cannot_save_it(): void
    {
        $this->organization = Organization::factory()->create();
        $viewer = $this->userWithPermissions('organization.view');

        $this->actingAs($viewer)->get(route('organization.profile.index'))
            ->assertOk()
            ->assertDontSee('Simpan Profil')
            ->assertSee('<fieldset  disabled', false);

        $this->actingAs($viewer)->postJson(route('organization.profile.update'), $this->validPayload())->assertForbidden();
    }

    public function test_a_user_without_permission_cannot_open_the_profile(): void
    {
        $this->actingAs($this->userWithPermissions('dashboard.view'))
            ->get(route('organization.profile.index'))
            ->assertForbidden();
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('organization.profile.index'))->assertRedirectToRoute('login');
    }

    public function test_an_administrator_is_sent_to_the_profile_when_opening_master_data_too_early(): void
    {
        $admin = $this->userWithPermissions('organization.update', 'venues.view', 'event-categories.view');

        $this->actingAs($admin)->get(route('master.venue.index'))
            ->assertRedirectToRoute('organization.profile.index')
            ->assertSessionHas('error', 'Profil organisasi belum diisi. Lengkapi profil organisasi terlebih dahulu.');

        $this->actingAs($admin)->get(route('master.event_category.index'))->assertRedirectToRoute('organization.profile.index');
    }

    public function test_master_data_becomes_available_right_after_the_first_save(): void
    {
        $admin = $this->userWithPermissions('organization.update', 'committee-roles.view');

        $this->actingAs($admin)->get(route('master.committee_role.index'))->assertRedirect();
        $this->actingAs($admin)->postJson(route('organization.profile.update'), $this->validPayload())->assertOk();

        $this->actingAs($admin)->get(route('master.committee_role.index'))->assertOk();
        $this->actingAs($admin)->getJson(route('master.committee_role.data'))->assertJsonPath('recordsTotal', 7);
    }

    public function test_dates_are_displayed_in_the_organizations_timezone(): void
    {
        $this->assertSame('Asia/Jakarta', $this->app->make(DisplayTime::class)->timezone());

        Organization::factory()->create(['timezone' => 'Asia/Jayapura']);

        $this->assertSame('Asia/Jayapura', $this->app->make(CurrentOrganization::class)->timezone());
        $this->assertSame('Asia/Jayapura', $this->app->make(DisplayTime::class)->timezone());
    }

    public function test_the_sidebar_offers_the_organization_menus_to_those_who_may_open_them(): void
    {
        $this->organization = Organization::factory()->create();
        $admin = $this->userWithPermissions('dashboard.view', 'organization.view', 'users.view', 'venues.view');

        $this->actingAs($admin)->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee(route('organization.profile.index'), false)
            ->assertSee(route('master.user.index'), false)
            ->assertSee(route('master.venue.index'), false)
            ->assertDontSee(route('master.committee_role.index'), false)
            ->assertDontSee(route('master.role.index'), false);
    }
}
