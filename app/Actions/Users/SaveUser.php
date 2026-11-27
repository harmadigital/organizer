<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CurrentOrganization;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaveUser
{
    public function __construct(
        private AuditLogger $audit,
        private CurrentOrganization $organization,
    ) {}

    /**
     * Create or update a person and, when asked, their login access, then audit what changed.
     *
     * The directory holds name, contact, unit, status and notes. Access is the login
     * access to apply; null leaves it untouched.
     *
     * @param  array<string, mixed>  $directory
     * @param  array{login_enabled: bool, username?: ?string, password?: ?string, role?: ?string}|null  $access
     */
    public function handle(User $user, array $directory, ?array $access): User
    {
        return DB::transaction(function () use ($user, $directory, $access): User {
            $isNew = ! $user->exists;
            $rolesBefore = $isNew ? [] : $this->roleNamesOf($user);

            $user->fill($directory);

            if ($access !== null) {
                $this->applyLoginAccess($user, $access);
            }

            $changedAttributes = $user->getDirty();
            $attributesBefore = $isNew ? [] : Arr::only($user->getRawOriginal(), array_keys($changedAttributes));

            $user->save();

            $rolesAfter = $access === null ? $rolesBefore : $this->rolesFor($access);

            if ($rolesAfter !== $rolesBefore) {
                $user->syncRoles($rolesAfter);
            }

            if ($changedAttributes !== [] || $rolesAfter !== $rolesBefore) {
                $this->audit->record(
                    action: 'users.'.($isNew ? 'created' : 'updated'),
                    auditable: $user,
                    before: $isNew ? [] : [...$attributesBefore, 'roles' => $rolesBefore],
                    after: [...$changedAttributes, 'roles' => $rolesAfter],
                    organizationId: $this->organization->id(),
                );
            }

            return $user;
        });
    }

    /**
     * Turning login off removes every way back in: password, two-factor secrets and "remember me".
     *
     * @param  array{login_enabled: bool, username?: ?string, password?: ?string, role?: ?string}  $access
     */
    private function applyLoginAccess(User $user, array $access): void
    {
        if (! $access['login_enabled']) {
            $user->forceFill([
                'password' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'remember_token' => null,
            ]);

            return;
        }

        $user->username = $access['username'];

        if (filled($access['password'] ?? null)) {
            $user->password = $access['password'];
            $user->setRememberToken(Str::random(60));
        }
    }

    /**
     * @param  array{login_enabled: bool, username?: ?string, password?: ?string, role?: ?string}  $access
     * @return list<string>
     */
    private function rolesFor(array $access): array
    {
        return $access['login_enabled'] && filled($access['role'] ?? null) ? [$access['role']] : [];
    }

    /**
     * @return list<string>
     */
    private function roleNamesOf(User $user): array
    {
        return $user->getRoleNames()->sort()->values()->all();
    }
}
