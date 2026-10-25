import type { PaginatedResponse, SortDirection } from '@/types/listing';

export type RawMaterialTransactionType =
    | 'opening'
    | 'purchase'
    | 'issue'
    | 'adjustment';

/*
|--------------------------------------------------------------------------
| Reference
|--------------------------------------------------------------------------
*/

export type RawMaterialTransactionReference = {
    type?: string;
    uuid?: string;
    label: string;
};

/*
|--------------------------------------------------------------------------
| Raw Material Transaction
|--------------------------------------------------------------------------
*/

export type RawMaterialTransaction = {
    id: number;
    uuid: string;

    section_size: {
        id: number;
        section_id: number | null;
        size: string | null;
        section: string | null;
    };

    type: {
        value: RawMaterialTransactionType | null;
        label: string;
    };

    quantity: number;

    user: {
        id: number;
        name: string | null;
        employee_code: string | null;
    } | null;

    reference: RawMaterialTransactionReference | null;
    reference_type: string | null;
    reference_id: number | null;

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

export type RawMaterialTransactionSort = 'created_at' | 'quantity' | 'type';

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

export type RawMaterialTransactionFilters = {
    search: string;
    sectionId: number | string;
    sectionSizeId: number | string;
    type: string;
    userId: number | string;
    startDate: string;
    endDate: string;
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
