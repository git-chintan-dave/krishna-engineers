<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;

trait HasFilter
{
    public function scopeFilterQuery(Builder $query, Request|array $request): Builder
    {
        $filters = $request instanceof Request
            ? $request->all()
            : $request;

        if ($query->getQuery()->columns === null) {
            $query->select($this->getTable().'.*');
        }

        $this->applySearch($query, $filters);
        $this->applyDateFilter($query, $filters);
        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        return $query;
    }

    protected function applySearch(Builder $query, array $filters): void
    {
        $search = $filters['search'] ?? null;

        if (! filled($search)) {
            return;
        }

        $searchable = property_exists($this, 'searchable')
            ? $this->searchable
            : ['name'];

        $query->where(function (Builder $searchQuery) use ($search, $searchable) {
            foreach ($searchable as $field) {
                if (str_contains($field, '.')) {
                    [$relation, $column] = explode('.', $field, 2);

                    $searchQuery->orWhereHas(
                        $relation,
                        fn (Builder $relationQuery) => $relationQuery->where(
                            $column,
                            'like',
                            "%{$search}%"
                        )
                    );

                    continue;
                }

                $searchQuery->orWhere(
                    $this->qualifyColumn($field),
                    'like',
                    "%{$search}%"
                );
            }
        });
    }

    protected function applyDateFilter(Builder $query, array $filters): void
    {
        $startDateColumn = property_exists($this, 'startDateFilterColumn')
            ? $this->startDateFilterColumn
            : 'created_at';

        $endDateColumn = property_exists($this, 'endDateFilterColumn')
            ? $this->endDateFilterColumn
            : $startDateColumn;

        $dateFilterable = property_exists($this, 'dateFilterable')
            ? $this->dateFilterable
            : [];

        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Exact Date Filters
        |--------------------------------------------------------------------------
        */

        foreach ($dateFilterable as $field) {
            $date = $filters[$field] ?? null;

            if (! filled($date)) {
                continue;
            }

            $column = $this->qualifyColumn($field);

            $query->whereRaw(
                "DATE({$column}) = DATE(?)",
                [$date],
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Date Range Filters
        |--------------------------------------------------------------------------
        */

        $startColumn = $this->qualifyColumn($startDateColumn);
        $endColumn = $this->qualifyColumn($endDateColumn);

        $query
            ->when(
                filled($startDate),
                fn (Builder $q) => $q->whereRaw(
                    "DATE({$startColumn}) >= DATE(?)",
                    [$startDate],
                ),
            )
            ->when(
                filled($endDate),
                fn (Builder $q) => $q->whereRaw(
                    "DATE({$endColumn}) <= DATE(?)",
                    [$endDate],
                ),
            );
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        $filterable = property_exists($this, 'filterable')
            ? $this->filterable
            : [];

        foreach ($filterable as $field) {
            $requestKey = str_contains($field, '.')
                ? explode('.', $field, 2)[0]
                : $field;

            $value = $filters[$requestKey] ?? null;

            if (! filled($value)) {
                continue;
            }

            $value = $this->normalizeFilterValue($value);

            if (str_contains($field, '.')) {
                [$relation, $column] = explode('.', $field, 2);

                $query->whereHas(
                    $relation,
                    function (Builder $relationQuery) use ($column, $value) {
                        is_array($value)
                            ? $relationQuery->whereIn($column, $value)
                            : $relationQuery->where($column, $value);
                    }
                );

                continue;
            }

            is_array($value)
                ? $query->whereIn($this->qualifyColumn($field), $value)
                : $query->where($this->qualifyColumn($field), $value);
        }
    }

    protected function applySorting(Builder $query, array $filters): void
    {
        $sortable = property_exists($this, 'sortable')
            ? $this->sortable
            : ['id'];

        $sort = $filters['sort'] ?? ($this->defaultSort ?? 'id');

        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        $direction = $filters['direction'] ?? ($this->defaultDirection ?? 'desc');

        $direction = $direction === 'asc' ? 'asc' : 'desc';

        if (! str_contains($sort, '.')) {
            $query->orderBy(
                $this->qualifyColumn($sort),
                $direction
            );

            return;
        }

        [$relationName, $column] = explode('.', $sort, 2);

        if (! method_exists($this, $relationName)) {
            $query->orderBy(
                $this->qualifyColumn('id'),
                $direction
            );

            return;
        }

        $relation = $this->{$relationName}();

        if ($relation instanceof BelongsTo) {
            $this->applyBelongsToSort(
                $query,
                $relation,
                $column,
                $direction
            );

            return;
        }

        if ($relation instanceof HasOne) {
            $this->applyHasOneSort(
                $query,
                $relation,
                $column,
                $direction
            );

            return;
        }

        $query->orderBy(
            $this->qualifyColumn('id'),
            $direction
        );
    }

    protected function applyBelongsToSort(
        Builder $query,
        BelongsTo $relation,
        string $column,
        string $direction
    ): void {
        $relatedModel = $relation->getRelated();

        $query
            ->leftJoin(
                $relatedModel->getTable(),
                $this->qualifyColumn($relation->getForeignKeyName()),
                '=',
                $relatedModel->qualifyColumn($relation->getOwnerKeyName())
            )
            ->select($this->getTable().'.*')
            ->orderBy(
                $relatedModel->qualifyColumn($column),
                $direction
            );
    }

    protected function applyHasOneSort(
        Builder $query,
        HasOne $relation,
        string $column,
        string $direction
    ): void {
        $relatedModel = $relation->getRelated();

        $query
            ->leftJoin(
                $relatedModel->getTable(),
                $relatedModel->qualifyColumn($relation->getForeignKeyName()),
                '=',
                $this->qualifyColumn($relation->getLocalKeyName())
            )
            ->select($this->getTable().'.*')
            ->orderBy(
                $relatedModel->qualifyColumn($column),
                $direction
            );
    }

    protected function normalizeFilterValue(mixed $value): mixed
    {
        if (is_string($value) && str_contains($value, ',')) {
            return array_values(
                array_filter(
                    explode(',', $value),
                    fn ($item) => $item !== ''
                )
            );
        }

        return $value;
    }
}
