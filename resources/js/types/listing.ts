/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

export type PaginationMeta = {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
};

export type PaginatedResponse<TData> = {
    data: TData[];
    meta: PaginationMeta;
};

export type PaginationState = {
    pageIndex: number;
    pageSize: number;
};

/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/

export type SortDirection = 'asc' | 'desc';

/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/

export type Status = 'active' | 'inactive';

export type StatusFilter = 'all' | Status;
