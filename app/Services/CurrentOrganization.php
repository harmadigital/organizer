<?php

namespace App\Services;

use App\Models\Organization;

/**
 * The organization the application is working for.
 *
 * The baseline is a single organization, so this is simply the first one.
 * Everything that needs "the organization" asks here, which keeps a later
 * move to several organizations in one place.
 */
class CurrentOrganization
{
    private ?Organization $organization = null;

    public function get(): ?Organization
    {
        return $this->organization ??= Organization::query()->oldest('id')->first();
    }

    public function id(): ?int
    {
        return $this->get()?->getKey();
    }

    public function exists(): bool
    {
        return $this->get() !== null;
    }

    public function set(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function timezone(): string
    {
        return $this->get()?->timezone ?? config('organizer.timezone');
    }
}
