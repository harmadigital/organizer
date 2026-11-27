<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_displays_user_summary_and_filters_without_a_branch_filter(): void
    {
        $this->seed(DatabaseSeeder::class);

        $role = Role::findByName('Super Admin');
        $activeUser = User::factory()->create();
        $inactiveUser = User::factory()->inactive()->create();
        $activeUser->assignRole($role);
        $inactiveUser->assignRole($role);
        User::factory()->withoutLogin()->create();

        $response = $this->actingAs($activeUser)->get(route('master.user.index'));

        $response->assertOk();
        $this->assertSame([
            'total' => 4,
            'active' => 3,
            'with_login' => 3,
        ], $response->viewData('summary'));
        $response->assertSee('Total User');
        $response->assertSee('User Aktif');
        $response->assertSee('Memiliki Akses Login');
        $response->assertSee('Cari nama, username, email, atau telepon...');
        $response->assertSee('Semua Role');
        $response->assertSee('Semua Status');
        $response->assertSee('Semua Akses');
        $response->assertDontSee('Semua Cabang');
        $response->assertSeeInOrder(['ui-page-header', 'ui-stat-card', 'ui-toolbar', 'kt_datatable_master', 'kt_modal_master'], false);

        $this->actingAs($activeUser)
            ->getJson(route('master.user.data', ['status' => 'inactive']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);
    }
}
