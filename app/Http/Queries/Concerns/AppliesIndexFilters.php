<?php

namespace App\Http\Queries\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait AppliesIndexFilters
{
    protected function applyIndexFilters(
        Builder $query,
        array $filters,
        array $searchColumns = [],
        array $sortColumns = [],
    ): Builder {
        $search = $filters['search'] ?? null;

        if ($search && $searchColumns) {
            $query->where(function (Builder $query) use (
                $search,
                $searchColumns,
            ): void {
                foreach ($searchColumns as $index => $column) {
                    $method = $index === 0
                        ? 'where'
                        : 'orWhere';

                    $query->{$method}(
                        $column,
                        'like',
                        "%{$search}%",
                    );
                }
            });
        }

        if (! empty($filters['sort'])) {
            $sort = $sortColumns[$filters['sort']]
                ?? $filters['sort'];

            return $query->orderBy(
                $sort,
                $filters['direction'] ?? 'asc',
            );
        }

        return $query->orderByDesc('id');
    }
}
