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
     * Units that may be chosen as a parent, for the form's remote dropdown. The unit being
     * edited and the units below it are left out, since choosing them would form a cycle.
     */
    public function options(Request $request): JsonResponse
    {
        $perPage = 20;
        $excluded = [];

        if ($request->filled('except')) {
            $unit = OrganizationUnit::query()->forOrganization($this->organization->id())->find($request->integer('except'));
            $excluded = $unit === null ? [] : [$unit->getKey(), ...$unit->descendantIds()];
        }

        $query = OrganizationUnit::query()
            ->forOrganization($this->organization->id())
            ->whereKeyNot($excluded)
            ->orderBy('name')
            ->orderBy('id');

        $this->applySearch($query, $request->string('search')->trim()->value());

        $units = $query->forPage(max(1, $request->integer('page', 1)), $perPage + 1)->get(['id', 'name', 'is_active']);

        return $this->jsonSuccess([
            'results' => $units->take($perPage)->map(fn (OrganizationUnit $unit): array => [
                'id' => $unit->getKey(),
                'text' => $unit->name,
                'description' => $unit->is_active ? null : 'Nonaktif',
            ])->values()->all(),
            'more' => $units->count() > $perPage,
        ]);
    }

    public function show(OrganizationUnit $organizationUnit): JsonResponse
    {
        return $this->jsonSuccess([
            ...$organizationUnit->toArray(),
            'parent_id_label' => $organizationUnit->parent?->name,
        ]);
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
