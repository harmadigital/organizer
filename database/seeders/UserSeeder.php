<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private const LOCAL_ONLY_PASSWORD = '123123123';

    /**
     * Create the first administrator account when it does not exist yet.
     */
    public function run(): void
    {
        $password = $this->resolvePassword();

        if ($password === null) {
            $this->command?->warn('Administrator account not seeded: set SEED_ADMIN_PASSWORD to create it in this environment.');

            return;
        }

        $admin = config('organizer.seed_admin');

        $user = User::firstOrCreate(
            [
                'email' => $admin['email'],
                'username' => $admin['username'],
            ],
            [
                'name' => $admin['name'],
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        $user->assignRole(PermissionSeeder::SUPER_ADMIN_ROLE);
    }

    /**
     * The well-known default password is only ever used on a developer machine or in tests.
     */
    private function resolvePassword(): ?string
    {
        $configured = config('organizer.seed_admin.password');

        if (filled($configured)) {
            return $configured;
        }

        return app()->environment(['local', 'testing']) ? self::LOCAL_ONLY_PASSWORD : null;
    }
}
