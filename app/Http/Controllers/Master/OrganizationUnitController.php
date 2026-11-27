<?php

namespace App\Http\Controllers\Master;

use App\Http\Requests\Master\SaveOrganizationUnitRequest;
use App\Models\OrganizationUnit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationUnitController extends MasterRecordController
{
    protected function subject(): string
    {
        return 'organization_units';
    }

    protected function searchableColumns(): array
    {
        return ['name', 'description'];
    }

    public function index(): View
    {
        return view('master.organization-unit');
    }

    public function data(Request $request): JsonResponse
    {
        return $this->listing(
            $request,
            OrganizationUnit::query()->with('parent:id,name')->withCount('users'),
        );
    }

    /**
     * Units that may be chosen as a parent, for the form's select box.
     */
    public function options(Request $request): JsonResponse
    {
        $units = OrganizationUnit::query()
            ->forOrganization($this->organization->id())
            ->when($request->integer('except'), fn ($query, int $unitId) => $query->whereKeyNot($unitId))
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        return $this->jsonSuccess($units);
    }

    public function show(OrganizationUnit $organizationUnit): JsonResponse
    {
        return $this->jsonSuccess($organizationUnit);
    }

    public function insert(SaveOrganizationUnitRequest $request): JsonResponse
    {
        return $this->store(new OrganizationUnit, $request->validated());
    }

    public function update(SaveOrganizationUnitRequest $request, OrganizationUnit $organizationUnit): JsonResponse
    {
        return $this->modify($organizationUnit, $request->validated());
    }

    public function delete(OrganizationUnit $organizationUnit): JsonResponse
    {
        return $this->remove($organizationUnit);
    }
}
