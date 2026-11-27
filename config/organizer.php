<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Display Timezone
    |--------------------------------------------------------------------------
    |
    | Dates are stored in UTC. This is the timezone used when a date is shown
    | to a person. It is the fallback until an organization profile provides
    | its own timezone.
    |
    */

    'timezone' => env('ORGANIZER_TIMEZONE', 'Asia/Jakarta'),

    /*
    |--------------------------------------------------------------------------
    | Audit Log
    |--------------------------------------------------------------------------
    |
    | Attribute names listed here are never written to the audit log in clear
    | text. The match is case-insensitive and applies at any nesting depth of
    | the "before" and "after" payloads.
    |
    */

    'audit' => [
        'redacted_keys' => [
            'password',
            'password_confirmation',
            'current_password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'token',
            'token_hash',
            'secret',
            'api_key',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeded Administrator
    |--------------------------------------------------------------------------
    |
    | Account created by the UserSeeder. Outside the local and testing
    | environments no account is created unless SEED_ADMIN_PASSWORD is set,
    | so a deployment never ends up with a well-known default password.
    |
    */

    'seed_admin' => [
        'name' => env('SEED_ADMIN_NAME', 'Super Admin'),
        'username' => env('SEED_ADMIN_USERNAME', 'superadmin'),
        'email' => env('SEED_ADMIN_EMAIL', 'admin@boilerplate.com'),
        'password' => env('SEED_ADMIN_PASSWORD'),
    ],

];
