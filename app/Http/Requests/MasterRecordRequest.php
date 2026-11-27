<?php

namespace App\Http\Requests;

use App\Services\CurrentOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * Base for requests that save a record owned by the current organization.
 *
 * Access is decided by the route's "can:" middleware, as on every other screen.
 */
abstract class MasterRecordRequest extends JsonFormRequest
{
    /**
     * Route parameter holding the record being updated; absent when creating.
     */
    abstract protected function routeParameter(): string;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    protected function organizationId(): ?int
    {
        return app(CurrentOrganization::class)->id();
    }

    protected function record(): ?Model
    {
        $record = $this->route($this->routeParameter());

        return $record instanceof Model ? $record : null;
    }

    /**
     * No other record of this organization may hold the same value.
     */
    protected function uniqueInOrganization(string $table, string $column): Unique
    {
        return Rule::unique($table, $column)
            ->where('organization_id', $this->organizationId())
            ->ignore($this->record());
    }

    /**
     * The referenced record must belong to this organization.
     */
    protected function existsInOrganization(string $table): Exists
    {
        return Rule::exists($table, 'id')->where('organization_id', $this->organizationId());
    }
}
