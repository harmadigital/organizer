<?php

namespace App\Http\Controllers\Master;

use App\Http\Requests\Master\SaveDivisionTemplateRequest;
use App\Models\DivisionTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DivisionTemplateController extends MasterRecordController
{
    protected function subject(): string
    {
        return 'division_templates';
    }

    protected function searchableColumns(): array
    {
        return ['name', 'description'];
    }

    public function index(): View
    {
        return view('master.division-template');
    }

    public function data(Request $request): JsonResponse
    {
        return $this->listing($request, DivisionTemplate::query());
    }

    public function show(DivisionTemplate $divisionTemplate): JsonResponse
    {
        return $this->jsonSuccess($divisionTemplate);
    }

    public function insert(SaveDivisionTemplateRequest $request): JsonResponse
    {
        return $this->store(new DivisionTemplate, $request->validated());
    }

    public function update(SaveDivisionTemplateRequest $request, DivisionTemplate $divisionTemplate): JsonResponse
    {
        return $this->modify($divisionTemplate, $request->validated());
    }

    public function delete(DivisionTemplate $divisionTemplate): JsonResponse
    {
        return $this->remove($divisionTemplate);
    }
}
