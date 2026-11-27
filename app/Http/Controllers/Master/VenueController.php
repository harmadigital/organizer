<?php

namespace App\Http\Controllers\Master;

use App\Http\Requests\Master\SaveVenueRequest;
use App\Models\Venue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueController extends MasterRecordController
{
    protected function subject(): string
    {
        return 'venues';
    }

    protected function searchableColumns(): array
    {
        return ['name', 'address'];
    }

    public function index(): View
    {
        return view('master.venue');
    }

    public function data(Request $request): JsonResponse
    {
        return $this->listing($request, Venue::query());
    }

    public function show(Venue $venue): JsonResponse
    {
        return $this->jsonSuccess($venue);
    }

    public function insert(SaveVenueRequest $request): JsonResponse
    {
        return $this->store(new Venue, $request->validated());
    }

    public function update(SaveVenueRequest $request, Venue $venue): JsonResponse
    {
        return $this->modify($venue, $request->validated());
    }

    public function delete(Venue $venue): JsonResponse
    {
        return $this->remove($venue);
    }
}
