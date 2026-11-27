<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Services\CurrentOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * For records owned by an organization.
 *
 * Route model binding only finds records of the current organization, so an
 * id that belongs to another organization answers 404 without any per-route check.
 */
trait BelongsToOrganization
{
    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeForOrganization(Builder $query, Organization|int|null $organization): void
    {
        $query->where($this->qualifyColumn('organization_id'), $organization instanceof Organization ? $organization->getKey() : $organization);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where($this->qualifyColumn('is_active'), true);
    }

    /**
     * @param  Builder<static>|Relation<static, *, *>  $query
     * @param  mixed  $value
     * @param  string|null  $field
     * @return Builder<static>|Relation<static, *, *>
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)
            ->where($this->qualifyColumn('organization_id'), app(CurrentOrganization::class)->id());
    }
}
