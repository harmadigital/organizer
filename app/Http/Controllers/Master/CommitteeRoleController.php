<?php

namespace App\Http\Controllers\Master;

use App\Actions\MasterData\SaveCommitteeRole;
use App\Enums\CommitteeRoleKind;
use App\Enums\CommitteeRoleScope;
use App\Enums\EventAbility;
use App\Exceptions\RecordInUseException;
use App\Http\Requests\Master\SaveCommitteeRoleRequest;
use App\Models\CommitteeRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CommitteeRoleController extends MasterRecordController
{
    protected function subject(): string
    {
        return 'committee_roles';
    }

    protected function searchableColumns(): array
    {
        return ['name', 'description'];
    }

    public function index(): View
    {
        return view('master.committee-role', [
            'abilityGroups' => EventAbility::grouped(),
            'scopes' => CommitteeRoleScope::cases(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = CommitteeRole::query()
            ->forOrganization($this->organization->id())
            ->withCount('abilities');

        if (! $request->has('order')) {
            $query->orderBy('sort_order')->orderBy('name');
        }

        $this->applySearch($query, $request->string('search')->trim()->value());
        $this->applyStatusFilter($query, $request->string('status')->value());

        return DataTables::eloquent($query)
            ->addColumn('kind_label', fn (CommitteeRole $role): string => $role->kind->label())
            ->addColumn('scope_label', fn (CommitteeRole $role): string => $role->scope->label())
            ->addColumn('is_built_in', fn (CommitteeRole $role): bool => $role->kind->isBuiltIn())
            ->make(true);
    }

    public function show(CommitteeRole $committeeRole): JsonResponse
    {
        return $this->jsonSuccess([
            ...$committeeRole->toArray(),
            'is_built_in' => $committeeRole->kind->isBuiltIn(),
            'is_supervisory' => $committeeRole->kind->isSupervisory(),
            'abilities' => $committeeRole->abilityValues(),
        ]);
    }

    public function insert(SaveCommitteeRoleRequest $request, SaveCommitteeRole $saveRole): JsonResponse
    {
        $role = new CommitteeRole([
            'organization_id' => $this->organization->id(),
            'kind' => CommitteeRoleKind::Custom,
        ]);

        return $this->jsonSuccess(
            $saveRole->handle($role, $request->roleAttributes(), $request->validated('abilities')),
            201,
        );
    }

    public function update(SaveCommitteeRoleRequest $request, CommitteeRole $committeeRole, SaveCommitteeRole $saveRole): JsonResponse
    {
        return $this->jsonSuccess(
            $saveRole->handle($committeeRole, $request->roleAttributes(), $request->validated('abilities')),
        );
    }

    /**
     * @throws RecordInUseException
     */
    public function delete(CommitteeRole $committeeRole): JsonResponse
    {
        if ($committeeRole->kind->isBuiltIn()) {
            return $this->jsonFailure('Jabatan bawaan tidak dapat dihapus. Nonaktifkan saja bila tidak digunakan.', 409);
        }

        return $this->remove($committeeRole);
    }
}
