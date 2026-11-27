<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\DisplayTime;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\WipeCommand;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function tearDown(): void
    {
        FreshCommand::prohibit(false);
        RefreshCommand::prohibit(false);
        ResetCommand::prohibit(false);
        WipeCommand::prohibit(false);

        parent::tearDown();
    }

    public function test_validation_messages_are_in_indonesian(): void
    {
        $validator = Validator::make(
            ['name' => '', 'email' => 'bukan-email', 'password' => 'abc'],
            ['name' => 'required', 'email' => 'email', 'password' => 'min:8'],
        );

        $this->assertSame('id', $this->app->getLocale());
        $this->assertSame('Kolom nama wajib diisi.', $validator->errors()->first('name'));
        $this->assertSame('Kolom email harus berupa alamat email yang valid.', $validator->errors()->first('email'));
        $this->assertSame('Kolom kata sandi minimal 8 karakter.', $validator->errors()->first('password'));
    }

    public function test_a_failed_login_is_explained_in_indonesian(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['username' => $user->username, 'password' => 'salah-sandi'])
            ->assertSessionHasErrors(['username' => 'Nama pengguna atau kata sandi tidak cocok dengan data kami.']);

        $this->assertGuest();
    }

    public function test_dates_are_displayed_in_the_configured_timezone(): void
    {
        config(['organizer.timezone' => 'Asia/Makassar']);

        $displayTime = $this->app->make(DisplayTime::class);

        $this->assertSame('Asia/Makassar', $displayTime->timezone());
        $this->assertSame('17 Agustus 2026 02:30', $displayTime->format(now()->setDateTime(2026, 8, 16, 18, 30)));
    }

    public function test_the_display_timezone_defaults_to_jakarta(): void
    {
        $this->assertSame('Asia/Jakarta', $this->app->make(DisplayTime::class)->timezone());
        $this->assertSame('UTC', config('app.timezone'));
    }

    public function test_the_layout_loads_the_design_layer_and_the_shared_ajax_helper(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('username', 'superadmin')->sole();

        $response = $this->actingAs($admin)->get(route('master.role.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'assets/css/style.bundle.css',
            'assets/css/ui.css',
            'assets/js/scripts.bundle.js',
            'assets/js/organizer/http.js',
            'assets/js/master/role.js',
        ], false);
        $this->assertFileExists(public_path('assets/css/ui.css'));
        $this->assertFileExists(public_path('assets/js/organizer/http.js'));
    }

    public function test_the_sidebar_lists_the_master_menus_for_a_user_who_may_open_them(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('username', 'superadmin')->sole();

        $this->actingAs($admin)->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee(route('master.user.index'), false)
            ->assertSee(route('master.role.index'), false);
    }

    public function test_user_and_role_management_have_their_own_menu_group_right_below_the_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('username', 'superadmin')->sole();

        $response = $this->actingAs($admin)->get(route('master.role.index'))->assertOk();

        $response->assertDontSee('Master User &amp; Role', false)
            ->assertDontSee('Master &amp; Seting', false)
            ->assertSeeInOrder([
                '<span class="menu-title">Dashboard</span>',
                'User &amp; Akses',
                route('master.user.index'),
                route('master.role.index'),
                '>Organisasi</span>',
                '<span class="menu-title">Master Data</span>',
            ], false);

        $masterDataGroup = str($response->getContent())->after('<span class="menu-title">Master Data</span>')->before('<!--end:Menu sub-->');
        $this->assertFalse($masterDataGroup->contains(route('master.user.index')));
        $this->assertFalse($masterDataGroup->contains(route('master.role.index')));
    }

    public function test_a_user_manager_sees_only_the_user_menu(): void
    {
        $this->seed(DatabaseSeeder::class);
        $userManager = User::factory()->create();
        $userManager->givePermissionTo(['dashboard.view', 'users.view']);

        $this->actingAs($userManager)->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('User &amp; Akses', false)
            ->assertSee(route('master.user.index'), false)
            ->assertDontSee(route('master.role.index'), false)
            ->assertDontSee('<span class="menu-title">Master Data</span>', false)
            ->assertDontSee(route('organization.profile.index'), false);
    }

    public function test_the_sidebar_hides_the_master_menus_from_a_user_who_may_not_open_them(): void
    {
        $this->seed(DatabaseSeeder::class);
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('dashboard.view');

        $this->actingAs($viewer)->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertDontSee(route('master.user.index'), false)
            ->assertDontSee(route('master.role.index'), false);
    }

    public function test_a_guest_opening_the_home_page_is_sent_to_login(): void
    {
        $this->get('/')->assertRedirectToRoute('login');
    }

    public function test_the_user_model_keeps_its_stored_polymorphic_type(): void
    {
        $this->assertSame(User::class, (new User)->getMorphClass());
    }

    public function test_commands_that_drop_tables_are_refused_in_production(): void
    {
        $this->app['env'] = 'production';
        (new AppServiceProvider($this->app))->boot();

        foreach (['migrate:fresh', 'migrate:refresh', 'migrate:reset', 'db:wipe'] as $command) {
            $this->artisan($command)
                ->expectsOutputToContain('prohibited')
                ->assertFailed();
        }
    }

    public function test_db_refresh_refuses_to_run_outside_local_and_testing(): void
    {
        $user = User::factory()->create();
        $this->app['env'] = 'staging';

        $this->artisan('db:refresh')
            ->expectsOutputToContain('only runs in the local or testing environment')
            ->assertFailed();

        $this->assertModelExists($user);
    }
}
