<?php

namespace App\Http\Controllers\Master;

use App\Actions\Access\DeleteRole;
use App\Actions\Access\SaveRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SaveRoleRequest;
use App\Services\AccountAuthority;
use Database\Seeders\PermissionSeeder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    public function __construct(private AccountAuthority $authority) {}

    public function index(Request $request): View
    {
        $permissions = Permission::query()->where('guard_name', 'web')->orderBy('name')->get();

        return view('master.role', [
            'permissionGroups' => $permissions->groupBy(fn (Permission $permission) => Str::before($permission->name, '.')),
            'actorPermissions' => $request->user()->getAllPermissions()->pluck('name')->all(),
            'roleSummary' => $this->summary(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $actor = $request->user();

        $query = Role::query()
            ->where('guard_name', 'web')
            ->select(['id', 'name', 'created_at', 'updated_at'])
            ->withCount(['permissions', 'users'])
            ->with('permissions:id,name');

        $search = $request->string('search')->trim()->value();

        if ($search !== '') {
            $query->whereRaw("name like ? escape '!'", ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%']);
        }

        return DataTables::eloquent($query)
            ->addColumn('is_system', fn (Role $role): bool => $this->isSystemRole($role))
            ->addColumn('can_delete', fn (Role $role): bool => $actor->can('roles.delete')
                && ! $this->isSystemRole($role)
                && $role->users_count === 0
                && $this->authority->canGrantRole($actor, $role))
            ->removeColumn('permissions')
            ->with('summary', $this->summary())
            ->make(true);
    }

    public function show(Request $request, Role $role): JsonResponse
    {
        return $this->jsonSuccess([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions()->orderBy('name')->pluck('name')->all(),
            'users_count' => $role->users()->count(),
            'is_system' => $this->isSystemRole($role),
            'is_locked' => $role->name === PermissionSeeder::SUPER_ADMIN_ROLE,
            'can_manage' => $this->authority->canGrantRole($request->user(), $role),
        ]);
    }

    public function insert(SaveRoleRequest $request, SaveRole $saveRole): JsonResponse
    {
        return $this->jsonSuccess($saveRole->handle(new Role, $request->validated('name'), $request->permissions()), 201);
    }

    public function update(SaveRoleRequest $request, Role $role, SaveRole $saveRole): JsonResponse
    {
        if ($role->name === PermissionSeeder::SUPER_ADMIN_ROLE) {
            return $this->jsonFailure('Role Super Admin selalu memiliki semua permission dan tidak dapat diubah.', 409);
        }

        return $this->jsonSuccess($saveRole->handle($role, $request->validated('name'), $request->permissions()));
    }

    public function delete(Request $request, Role $role, DeleteRole $deleteRole): JsonResponse
    {
        if ($this->isSystemRole($role)) {
            return $this->jsonFailure('Role bawaan sistem tidak dapat dihapus.', 409);
        }

        if (! $this->authority->canGrantRole($request->user(), $role)) {
            return $this->jsonFailure('Anda tidak dapat menghapus role yang memiliki hak akses melebihi hak akses Anda.', 403);
        }

        $holders = $role->users()->count();

        if ($holders > 0) {
            return $this->jsonFailure("Role ini masih dipakai oleh {$holders} user. Ganti role mereka terlebih dahulu.", 409);
        }

        $deleteRole->handle($role);

        return $this->jsonSuccess();
    }

    /**
     * @return array{total: int, permissions: int, used: int}
     */
    private function summary(): array
    {
        return [
            'total' => Role::query()->where('guard_name', 'web')->count(),
            'permissions' => Permission::query()->where('guard_name', 'web')->count(),
            'used' => Role::query()->where('guard_name', 'web')->has('users')->count(),
        ];
    }

    private function isSystemRole(Role $role): bool
    {
        return in_array($role->name, PermissionSeeder::SYSTEM_ROLES, true);
    }
}
