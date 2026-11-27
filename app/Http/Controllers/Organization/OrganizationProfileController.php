<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\SaveOrganizationProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\SaveOrganizationProfileRequest;
use App\Models\Organization;
use App\Services\CurrentOrganization;
use App\Services\DisplayTime;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class OrganizationProfileController extends Controller
{
    /**
     * Timezones offered in the form. Any valid timezone is accepted on save.
     *
     * @var array<string, string>
     */
    private const TIMEZONES = [
        'Asia/Jakarta' => 'WIB (Asia/Jakarta)',
        'Asia/Makassar' => 'WITA (Asia/Makassar)',
        'Asia/Jayapura' => 'WIT (Asia/Jayapura)',
    ];

    public function index(CurrentOrganization $current, DisplayTime $displayTime): View
    {
        $organization = $current->get() ?? new Organization;

        return view('organization.profile', [
            'organization' => $organization,
            'timezones' => self::TIMEZONES + [$organization->timezone => $organization->timezone],
            'lastUpdatedAt' => $displayTime->format($organization->updated_at),
        ]);
    }

    public function update(SaveOrganizationProfileRequest $request, SaveOrganizationProfile $saveProfile): JsonResponse
    {
        return $this->jsonSuccess($saveProfile->handle($request->validated()));
    }
}
