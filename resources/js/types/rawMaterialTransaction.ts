import type {
    PaginatedResponse,
    SortDirection,
} from '@/types/listing';

/*
|--------------------------------------------------------------------------
| Raw Material Transaction
|--------------------------------------------------------------------------
*/

export type RawMaterialTransactionReference = {
    type: string;
    label: string;
};

export type RawMaterialTransaction = {
    id: number;
    uuid: string;
    section_size: {
        id: number;
        size: string;
        section: string | null;
    };
    type: {
        value: string;
        label: string;
    };
    quantity: number;
    user: {
        id: number;
        name: string | null;
        employee_code: string | null;
    };
    reference: RawMaterialTransactionReference | null;
    remarks: string | null;
    created_at: string;
    updated_at: string;
};

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

export type RawMaterialTransactionFormData = {
    section_size_id: number | null;
    type: string | null;
    quantity: number;
    reference_type: string;
    reference_id: number | null;
    remarks: string;
};

/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/

export type RawMaterialTransactionSort =
    | 'created_at'
    | 'quantity'
    | 'type';

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

export type RawMaterialTransactionFilters = {
    search: string;
    sectionSizeId: number | string;
    type: string;
    userId: number | string;
    fromDate: string;
    toDate: string;
    perPage: number;
    sort: RawMaterialTransactionSort;
    direction: SortDirection;
};

/*
|--------------------------------------------------------------------------
| Paginated Response
|--------------------------------------------------------------------------
*/

export type RawMaterialTransactionsResponse =
    PaginatedResponse<RawMaterialTransaction>;

/*
|--------------------------------------------------------------------------
| Select Option
|--------------------------------------------------------------------------
*/

export type RawMaterialTransactionSelectOption = {
    value: number | string;
    label: string;
};
