import type {
    PaginatedResponse,
    SortDirection,
} from '@/types/listing';

/*
|--------------------------------------------------------------------------
| Section
|--------------------------------------------------------------------------
*/

export type SectionSizeSection = {
    id: number;
    name: string;
};

/*
|--------------------------------------------------------------------------
| Section Size
|--------------------------------------------------------------------------
*/

export type SectionSize = {
    id: number;
    uuid: string;
    section_id: number;
    section: SectionSizeSection;
    size: string;
    quantity: number;
    weight: string;
    created_at: string | null;
    updated_at: string | null;
};

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

export type SectionSizeOption = {
    value: number;
    label: string;
};

export type SectionSizeFormData = {
    section_id: number | null;
    size: string;
    quantity?: number | null;
    weight?: string;
};

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

export type SectionSizeSort =
    | 'size'
    | 'quantity'
    | 'weight'
    | 'created_at';

export type SectionSizeFilters = {
    search: string;
    sectionId: number | 'all';
    perPage: number;
    sort: SectionSizeSort;
    direction: SortDirection;
};

/*
|--------------------------------------------------------------------------
| Responses
|--------------------------------------------------------------------------
*/

export type SectionSizesResponse =
    PaginatedResponse<SectionSize>;

export type SectionSizeSectionsResponse =
    SectionSizeSection[];
