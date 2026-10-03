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
    section_size: {
        id: number;
        size: string;
        section: string | null;
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
    | 'user_id'
    | 'part_id'
    | 'section_size_id'
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
| Options
|--------------------------------------------------------------------------
*/

export type BandsawWorkLogSelectOption<T = number | string> = {
    value: T;
    label: string;
};

/*
|--------------------------------------------------------------------------
| Last Report
|--------------------------------------------------------------------------
*/

export type BandsawWorkLogLastReport = {
    part_id: number;
    section_size_id: number;
};

/*
|--------------------------------------------------------------------------
| Form Options
|--------------------------------------------------------------------------
*/

export type BandsawWorkLogFormOptions = {
    bandsaws: BandsawWorkLogSelectOption<number>[];

    parts: BandsawWorkLogSelectOption<number>[];

    sectionSizes: BandsawWorkLogSelectOption<number>[];

    currentUserId: number;

    canSelectBandsaw: boolean;

    lastReports: Record<string, BandsawWorkLogLastReport>;
};
