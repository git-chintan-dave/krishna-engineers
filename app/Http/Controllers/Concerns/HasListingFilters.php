<?php

namespace App\Http\Controllers\Concerns;

trait HasListingFilters
{
    protected function listingFilters(array $filters): array
    {
        return [
            'search' => $filters['search'] ?? '',
            'perPage' => $filters['per_page'] ?? 10,
            'sort' => $filters['sort'] ?? 'id',
            'direction' => $filters['direction'] ?? 'desc',
        ];
    }
}
