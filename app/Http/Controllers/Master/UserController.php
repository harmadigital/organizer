<?php

namespace App\Http\Controllers\Master;

use App\Actions\Users\SaveUser;
use App\Enums\UserStatus;
use App\Http\Requests\Master\SaveUserRequest;
use App\Models\OrganizationUnit;
use App\Models\User;
use App\Services\AccountAuthority;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

/**
 * The single directory of people: committee members, organizers and administrators.
 * Login access is optional per person.
 */
class UserController extends MasterRecordController
{
    /**
     * Columns sent to the list. The password hash and two-factor secrets are never selected.
     *
     * @var list<string>
     */
    private const LIST_COLUMNS = ['id', 'name', 'username', 'email', 'phone', 'organization_unit_id', 'status', 'created_at', 'updated_at'];

    protected function subject(): string
    {
        return 'users';
    }

    protected function searchableColumns(): array
    {
        return ['name', 'username', 'email', 'phone'];
    }

    public function index(Request $request, AccountAuthority $authority): View
    {
        return view('master.user', [
            'units' => OrganizationUnit::query()
                ->forOrganization($this->organization->id())
                ->orderBy('name')
                ->get(['id', 'name', 'is_active']),
            'statuses' => UserStatus::cases(),
            'roles' => DB::table('roles')->where('guard_name', 'web')->orderBy('name')->pluck('name'),
            'grantableRoles' => $authority->grantableRoles($request->user())->pluck('name'),
            'summary' => $this->summary(),
        ]);
    }

    public function data(Request $request, AccountAuthority $authority): JsonResponse
    {
        $actor = $request->user();

        $query = User::query()
            ->select(self::LIST_COLUMNS)
            ->selectRaw('case when password is null then 0 else 1 end as has_password')
            ->with(['unit:id,name', 'roles:id,name', 'roles.permissions:id,name', 'permissions:id,name']);

        $this->applySearch($query, $request->string('search')->trim()->value());
        $this->applyFilters($query, $request);

        return DataTables::eloquent($query)
            ->addColumn('status_label', fn (User $user): string => $user->status->label())
            ->addColumn('unit_name', fn (User $user): ?string => $user->unit?->name)
            ->addColumn('role_names', fn (User $user): string => $user->roles->pluck('name')->sort()->implode(', '))
            ->addColumn('has_login', fn (User $user): bool => $user->username !== null && (bool) $user->has_password)
            ->addColumn('can_delete', fn (User $user): bool => $actor->can('users.delete')
                && ! $user->is($actor)
                && $authority->canManage($actor, $user))
            ->removeColumn('has_password', 'roles', 'permissions', 'unit')
            ->with('summary', $this->summary())
            ->make(true);
    }

    public function show(Request $request, User $user, AccountAuthority $authority): JsonResponse
    {
        $actor = $request->user();

        return $this->jsonSuccess([
            ...$user->only(['id', 'name', 'email', 'phone', 'organization_unit_id', 'notes', 'username']),
            'status' => $user->status->value,
            'login_enabled' => $user->hasLoginAccess(),
            'role' => $user->getRoleNames()->first(),
            'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
            'is_self' => $user->is($actor),
            'can_manage_access' => $actor->can('users.manage-access') && $authority->canManage($actor, $user),
        ]);
    }

    public function insert(SaveUserRequest $request, SaveUser $saveUser): JsonResponse
    {
        return $this->jsonSuccess($saveUser->handle(new User, $request->directory(), $request->access()), 201);
    }

    public function update(SaveUserRequest $request, User $user, SaveUser $saveUser): JsonResponse
    {
        return $this->jsonSuccess($saveUser->handle($user, $request->directory(), $request->access()));
    }

    public function delete(Request $request, User $user, AccountAuthority $authority): JsonResponse
    {
        if ($user->is($request->user())) {
            return $this->jsonFailure('Anda tidak dapat menghapus akun Anda sendiri.', 409);
        }

        if (! $authority->canManage($request->user(), $user)) {
            return $this->jsonFailure('Anda tidak dapat menghapus user yang memiliki hak akses melebihi hak akses Anda.', 403);
        }

        return $this->remove($user);
    }

    /**
     * Turn off two-factor login for someone who lost their authenticator device.
     */
    public function resetTwoFactor(Request $request, User $user, AccountAuthority $authority): JsonResponse
    {
        if (! $authority->canManage($request->user(), $user)) {
            return $this->jsonFailure('Anda tidak dapat mengubah akses user yang memiliki hak akses melebihi hak akses Anda.', 403);
        }

        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'remember_token' => null,
            ])->save();

            $this->audit->record(
                action: 'users.two_factor_reset',
                auditable: $user,
                organizationId: $this->organization->id(),
            );
        });

        return $this->jsonSuccess();
    }

    /**
     * Figures for the cards above the list.
     *
     * @return array{total: int, active: int, with_login: int}
     */
    private function summary(): array
    {
        return [
            'total' => User::query()->count(),
            'active' => User::query()->where('status', UserStatus::Active)->count(),
            'with_login' => User::query()->whereNotNull('username')->whereNotNull('password')->count(),
        ];
    }

    /**
     * @param  Builder<User>  $query
     */
    private function applyFilters(Builder $query, Request $request): void
    {
        if ($status = UserStatus::tryFrom($request->string('status')->value())) {
            $query->where('status', $status);
        }

        if ($request->filled('organization_unit_id')) {
            $query->where('organization_unit_id', $request->integer('organization_unit_id'));
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn (Builder $roles) => $roles->where('name', $request->string('role')->value()));
        }

        match ($request->string('access')->value()) {
            'with' => $query->whereNotNull('username')->whereNotNull('password'),
            'without' => $query->where(fn (Builder $query) => $query->whereNull('username')->orWhereNull('password')),
            default => null,
        };
    }
}
