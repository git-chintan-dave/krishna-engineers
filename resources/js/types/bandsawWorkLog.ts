import type {
    PaginatedResponse,
    SortDirection,
} from '@/types/listing';

/*
|--------------------------------------------------------------------------
| Bandsaw Work Log
|--------------------------------------------------------------------------
*/

export type BandsawWorkLog = {
    id: number;
    uuid: string;
    work_date: string;

    user: {
        id: number;
        name: string;
        employee_code: string;
    };

    part: {
        id: number;
        name: string;
        reference_name: string;
    };

    section: {
        id: number | null;
        name: string | null;
    };

    section_size: {
        id: number;
        size: string;
    };

    quantity: number;
    remarks: string | null;
    created_at: string;
    updated_at: string;
};

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

export type BandsawWorkLogFormData = {
    user_id: number | null;
    work_date: string;

    part_id: number | null;
    section_id: number | null;
    section_size_id: number | null;

    quantity: number;
    remarks: string;
};

/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/

export type BandsawWorkLogSort =
    | 'work_date'
    | 'quantity'
    | 'created_at';

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

export type BandsawWorkLogFilters = {
    search: string;
    workDate: string;
    userId: number | string;
    partId: number | string;
    sectionId: number | string;
    sectionSizeId: number | string;
    perPage: number;
    sort: BandsawWorkLogSort;
    direction: SortDirection;
};

/*
|--------------------------------------------------------------------------
| Paginated Response
|--------------------------------------------------------------------------
*/

export type BandsawWorkLogsResponse =
    PaginatedResponse<BandsawWorkLog>;

/*
|--------------------------------------------------------------------------
| Last Report
|--------------------------------------------------------------------------
*/

export type BandsawWorkLogLastReport = {
    part_id: number;
    section_id: number;
    section_size_id: number;
};

/*
|--------------------------------------------------------------------------
| Form Options
|--------------------------------------------------------------------------
*/

export type BandsawWorkLogFormOptions = {
    currentUserId: number;
    canSelectBandsaw: boolean;
    lastReports: Record<
        string,
        BandsawWorkLogLastReport
    >;
};
