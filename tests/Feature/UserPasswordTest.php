<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPasswordTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const PASSWORD_WITH_SPECIAL_CHARACTERS = ' P@ss<word>&"quoted" ';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->admin = User::query()->where('username', 'superadmin')->sole();
    }

    /**
     * @return array<string, mixed>
     */
    private function newUserWithLogin(string $password): array
    {
        return [
            'name' => 'Petugas Baru',
            'email' => 'petugas@example.com',
            'status' => 'active',
            'login_enabled' => '1',
            'username' => 'petugas',
            'password' => $password,
            'password_confirmation' => $password,
            'role' => 'Super Admin',
        ];
    }

    public function test_a_new_users_password_is_stored_exactly_as_typed(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('master.user.insert'), $this->newUserWithLogin(self::PASSWORD_WITH_SPECIAL_CHARACTERS))
            ->assertCreated()
            ->assertJsonPath('status', true);

        $user = User::query()->where('username', 'petugas')->sole();
        $this->assertTrue(Hash::check(self::PASSWORD_WITH_SPECIAL_CHARACTERS, $user->password));
    }

    public function test_a_user_can_sign_in_with_a_password_containing_special_characters(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('master.user.insert'), $this->newUserWithLogin('Rahasia<&>2026'))
            ->assertCreated();

        $this->post('/logout');
        $this->assertGuest();

        $this->post('/login', ['username' => 'petugas', 'password' => 'Rahasia<&>2026']);

        $this->assertAuthenticatedAs(User::query()->where('username', 'petugas')->sole());
    }

    public function test_changing_a_users_password_stores_it_exactly_as_typed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)->postJson(route('master.user.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
            'login_enabled' => '1',
            'username' => $user->username,
            'password' => self::PASSWORD_WITH_SPECIAL_CHARACTERS,
            'password_confirmation' => self::PASSWORD_WITH_SPECIAL_CHARACTERS,
        ])->assertOk()->assertJsonPath('status', true);

        $this->assertTrue(Hash::check(self::PASSWORD_WITH_SPECIAL_CHARACTERS, $user->fresh()->password));
    }

    public function test_updating_a_user_without_a_password_keeps_the_current_one(): void
    {
        $user = User::factory()->create();
        $currentHash = $user->password;

        $this->actingAs($this->admin)->postJson(route('master.user.update', $user), [
            'name' => 'Nama Diperbarui',
            'email' => $user->email,
            'status' => 'active',
            'login_enabled' => '1',
            'username' => $user->username,
            'password' => '',
            'password_confirmation' => '',
        ])->assertOk()->assertJsonPath('status', true);

        $this->assertSame($currentHash, $user->fresh()->password);
        $this->assertSame('Nama Diperbarui', $user->fresh()->name);
    }
}
