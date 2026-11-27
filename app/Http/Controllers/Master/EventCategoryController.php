<?php

namespace App\Http\Controllers\Master;

use App\Http\Requests\Master\SaveEventCategoryRequest;
use App\Models\EventCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventCategoryController extends MasterRecordController
{
    protected function subject(): string
    {
        return 'event_categories';
    }

    protected function searchableColumns(): array
    {
        return ['name', 'description'];
    }

    public function index(): View
    {
        return view('master.event-category');
    }

    public function data(Request $request): JsonResponse
    {
        return $this->listing($request, EventCategory::query());
    }

    public function show(EventCategory $eventCategory): JsonResponse
    {
        return $this->jsonSuccess($eventCategory);
    }

    public function insert(SaveEventCategoryRequest $request): JsonResponse
    {
        return $this->store(new EventCategory, $request->validated());
    }

    public function update(SaveEventCategoryRequest $request, EventCategory $eventCategory): JsonResponse
    {
        return $this->modify($eventCategory, $request->validated());
    }

    public function delete(EventCategory $eventCategory): JsonResponse
    {
        return $this->remove($eventCategory);
    }
}
