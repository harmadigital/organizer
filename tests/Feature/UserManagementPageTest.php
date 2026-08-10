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
        $activeUser = User::factory()->create(['email_verified_at' => now()]);
        $inactiveUser = User::factory()->unverified()->create();
        $activeUser->assignRole($role);
        $inactiveUser->assignRole($role);

        $response = $this->actingAs($activeUser)->get(route('master.user.index'));

        $response->assertOk();
        $this->assertSame([
            'total' => 3,
            'active' => 2,
            'inactive' => 1,
        ], $response->viewData('userSummary'));
        $response->assertSee('Total User');
        $response->assertSee('Active');
        $response->assertSee('Inactive');
        $response->assertSee('Cari nama, email...');
        $response->assertSee('Semua Role');
        $response->assertSee('Semua Status');
        $response->assertSee('filter_user_submit', false);
        $response->assertDontSee('Semua Cabang');
        $response->assertSeeInOrder([
            'data-user-section="header"',
            'data-user-section="counter"',
            'data-user-section="filter"',
            'data-user-section="list"',
        ], false);

        $this->actingAs($activeUser)
            ->getJson(route('master.user.data', ['status' => 'inactive']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);
    }
}
