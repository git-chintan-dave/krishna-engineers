import type {PaginatedResponse, SortDirection, Status, StatusFilter,} from '@/types/listing';

/*
|--------------------------------------------------------------------------
| Setup
|--------------------------------------------------------------------------
*/

export type Setup = {
    id: number;
    uuid: string;
    name: string;
    status: Status;
    created_at: string;
};

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

export type SetupFormData = {
    name: string;
    status: boolean;
};

/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/

export type SetupSort = 'name' | 'status' | 'created_at';

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

export type SetupFilters = {
    search: string;
    status: StatusFilter;
    perPage: number;
    sort: SetupSort;
    direction: SortDirection;
};

/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

export type SetupsResponse = PaginatedResponse<Setup>;
