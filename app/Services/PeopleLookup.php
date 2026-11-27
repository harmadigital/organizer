<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Searches active people for the remote dropdowns, in the shape select2 reads.
 */
class PeopleLookup
{
    public const PER_PAGE = 20;

    /**
     * @return array{results: list<array{id: int, text: string, description: string, has_login: bool}>, more: bool}
     */
    public function search(string $term, int $page): array
    {
        $query = User::query()
            ->where('status', UserStatus::Active)
            ->select(['id', 'name', 'username', 'organization_unit_id'])
            ->selectRaw('case when password is null then 0 else 1 end as has_password')
            ->with('unit:id,name')
            ->orderBy('name')
            ->orderBy('id');

        $term = trim($term);

        if ($term !== '') {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
            $query->where(fn (Builder $people) => $people
                ->whereRaw("name like ? escape '!'", [$pattern])
                ->orWhereRaw("username like ? escape '!'", [$pattern])
                ->orWhereRaw("email like ? escape '!'", [$pattern])
                ->orWhereRaw("phone like ? escape '!'", [$pattern]));
        }

        $people = $query->forPage(max(1, $page), self::PER_PAGE + 1)->get();

        return [
            'results' => $people->take(self::PER_PAGE)->map(function (User $person): array {
                $hasLogin = $person->username !== null && (bool) $person->has_password;

                return [
                    'id' => $person->getKey(),
                    'text' => $person->name,
                    'description' => implode(' · ', array_filter([$person->unit?->name, $hasLogin ? null : 'Tanpa akses login'])),
                    'has_login' => $hasLogin,
                ];
            })->values()->all(),
            'more' => $people->count() > self::PER_PAGE,
        ];
    }
}
