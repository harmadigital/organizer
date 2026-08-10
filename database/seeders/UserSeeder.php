<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.reset-two-factor',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'permissions.view',
            'permissions.create',
            'permissions.update',
            'permissions.delete',
        ];

        Permission::query()->delete();

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'Super Admin']);
        $role->syncPermissions($permissions);

        // create user and assign role
        $user = User::firstOrCreate(
            [
                'email' => 'admin@boilerplate.com',
                'username' => 'superadmin',
            ],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('123123123'),
                'email_verified_at' => now(),
            ]
        );

        $user->assignRole($role);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
