<?php

namespace App\Http\Controllers\Master;

use App\Actions\MasterData\DeleteMasterRecord;
use App\Actions\MasterData\SaveMasterRecord;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\CurrentOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/**
 * Shared behaviour of the list-and-modal screens for organization master data.
 */
abstract class MasterRecordController extends Controller
{
    public function __construct(
        protected CurrentOrganization $organization,
        protected SaveMasterRecord $saveRecord,
        protected DeleteMasterRecord $deleteRecord,
        protected AuditLogger $audit,
    ) {}

    /**
     * Audit prefix of the records this screen manages, e.g. "venues".
     */
    abstract protected function subject(): string;

    /**
     * Columns the search box looks in.
     *
     * @return list<string>
     */
    abstract protected function searchableColumns(): array;

    /**
     * Rows of the current organization, narrowed by the search box and the status filter.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    protected function listing(Request $request, Builder $query): JsonResponse
    {
        $query->where($query->qualifyColumn('organization_id'), $this->organization->id());

        $this->applySearch($query, $request->string('search')->trim()->value());
        $this->applyStatusFilter($query, $request->string('status')->value());

        return DataTables::eloquent($query)->make(true);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function store(Model $record, array $attributes): JsonResponse
    {
        $record->setAttribute('organization_id', $this->organization->id());

        return $this->jsonSuccess($this->saveRecord->handle($record, $attributes, $this->subject()), 201);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function modify(Model $record, array $attributes): JsonResponse
    {
        return $this->jsonSuccess($this->saveRecord->handle($record, $attributes, $this->subject()));
    }

    protected function remove(Model $record): JsonResponse
    {
        $this->deleteRecord->handle($record, $this->subject());

        return $this->jsonSuccess();
    }

    /**
     * Match the term anywhere in the searchable columns. "%" and "_" typed by the
     * user are matched literally; an explicit escape character keeps this the same
     * on MySQL and SQLite.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    protected function applySearch(Builder $query, string $term): void
    {
        if ($term === '') {
            return;
        }

        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        $query->where(function (Builder $query) use ($pattern): void {
            foreach ($this->searchableColumns() as $column) {
                $wrappedColumn = $query->getGrammar()->wrap($query->qualifyColumn($column));

                $query->orWhereRaw("{$wrappedColumn} like ? escape '!'", [$pattern]);
            }
        });
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    protected function applyStatusFilter(Builder $query, string $status): void
    {
        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where($query->qualifyColumn('is_active'), $status === 'active');
        }
    }
}
