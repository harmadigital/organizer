<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_user_can_update_their_profile_and_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('profile.update_ajax'), [
                'name' => 'Nama Baru',
                'email' => 'baru@example.com',
                'password' => 'Sandi-Baru-123',
                'password_confirmation' => 'Sandi-Baru-123',
            ])
            ->assertOk()
            ->assertJson(['status' => true]);

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('baru@example.com', $user->email);
        $this->assertTrue(Hash::check('Sandi-Baru-123', $user->password));
    }

    public function test_the_password_is_never_written_to_the_log(): void
    {
        Log::spy();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('profile.update_ajax'), [
                'name' => 'Nama Baru',
                'email' => $user->email,
                'password' => 'Sandi-Rahasia-987',
                'password_confirmation' => 'Sandi-Rahasia-987',
            ])
            ->assertOk()
            ->assertJson(['status' => true]);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($user): bool {
                $written = $message.json_encode($context);

                return $context['user_id'] === $user->id
                    && in_array('password', $context['changed'], true)
                    && ! str_contains($written, 'Sandi-Rahasia-987')
                    && ! str_contains($written, $user->fresh()->password);
            });
    }

    public function test_a_guest_cannot_update_a_profile(): void
    {
        $this->postJson(route('profile.update_ajax'), ['name' => 'X', 'email' => 'x@example.com'])
            ->assertUnauthorized();
    }
}
