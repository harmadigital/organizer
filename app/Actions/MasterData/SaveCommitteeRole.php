<?php

namespace App\Actions\MasterData;

use App\Enums\EventAbility;
use App\Models\CommitteeRole;
use App\Services\AuditLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveCommitteeRole
{
    /**
     * What makes a built-in role what it is. These never change after provisioning.
     */
    private const IDENTITY_ATTRIBUTES = ['key', 'kind', 'scope'];

    public function __construct(private AuditLogger $audit) {}

    /**
     * Create or update a committee role together with the abilities it grants.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $abilities
     *
     * @throws ValidationException
     */
    public function handle(CommitteeRole $role, array $attributes, array $abilities): CommitteeRole
    {
        $abilities = array_values(array_unique($abilities));
        sort($abilities);

        return DB::transaction(function () use ($role, $attributes, $abilities): CommitteeRole {
            $isNew = ! $role->exists;

            if ($isNew) {
                $attributes = Arr::except($attributes, ['key', 'kind']);
                $attributes['key'] = $this->uniqueKeyFor($role->organization_id, $attributes['name']);
            } elseif ($role->kind->isBuiltIn()) {
                $attributes = Arr::except($attributes, self::IDENTITY_ATTRIBUTES);
            } else {
                $attributes = Arr::except($attributes, ['key', 'kind']);
            }

            $role->fill($attributes);
            $this->guardSeparationOfDuties($role, $abilities);

            $changedAttributes = $role->getDirty();
            $attributesBefore = $isNew ? [] : Arr::only($role->getRawOriginal(), array_keys($changedAttributes));
            $abilitiesBefore = $isNew ? [] : $role->abilityValues();

            $role->save();

            if ($abilitiesBefore !== $abilities) {
                $role->abilities()->whereNotIn('ability', $abilities)->delete();

                foreach (array_diff($abilities, $abilitiesBefore) as $ability) {
                    $role->abilities()->create(['ability' => $ability]);
                }
            }

            if ($changedAttributes !== [] || $abilitiesBefore !== $abilities) {
                $this->audit->record(
                    action: 'committee_roles.'.($isNew ? 'created' : 'updated'),
                    auditable: $role,
                    before: $isNew ? [] : [...$attributesBefore, 'abilities' => $abilitiesBefore],
                    after: [...$changedAttributes, 'abilities' => $abilities],
                    organizationId: $role->organization_id,
                );
            }

            return $role;
        });
    }

    /**
     * A supervisory role reviews the committee's work, so it may not be given the means to change it.
     *
     * @param  list<string>  $abilities
     *
     * @throws ValidationException
     */
    private function guardSeparationOfDuties(CommitteeRole $role, array $abilities): void
    {
        if (! $role->kind->isSupervisory()) {
            return;
        }

        $notAllowed = array_filter(
            $abilities,
            fn (string $ability): bool => ! EventAbility::from($ability)->isAllowedForSupervisor(),
        );

        if ($notAllowed !== []) {
            throw ValidationException::withMessages([
                'abilities' => 'Jabatan pengawas hanya boleh melihat data dan mencatat keputusan, tidak mengubah pekerjaan panitia.',
            ]);
        }
    }

    private function uniqueKeyFor(int $organizationId, string $name): string
    {
        $base = Str::limit(Str::slug($name, '_') ?: 'jabatan', 50, '');
        $key = $base;
        $suffix = 2;

        while (CommitteeRole::query()->forOrganization($organizationId)->where('key', $key)->exists()) {
            $key = $base.'_'.$suffix++;
        }

        return $key;
    }
}
