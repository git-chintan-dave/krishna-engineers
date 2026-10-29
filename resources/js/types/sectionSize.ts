import type { PaginatedResponse, SortDirection } from '@/types/listing';
import { SectionSizeSchemaData } from '@/schemas/sectionSizeSchema';
import {StockFilter} from "@/types/tool";

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

export type SectionSizeFormData = SectionSizeSchemaData;

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

export type SectionSizeSort = 'size' | 'quantity' | 'weight' | 'created_at';

export type SectionSizeFilters = {
    search: string;
    sectionId: number | 'all';
    perPage: number;
    sort: SectionSizeSort;
    direction: SortDirection;
    stock: StockFilter | null;
};

/*
|--------------------------------------------------------------------------
| Responses
|--------------------------------------------------------------------------
*/

export type SectionSizesResponse = PaginatedResponse<SectionSize>;

export type SectionSizeSectionsResponse = SectionSizeSection[];

export interface SectionSizeStatistics {
    total: number;
    total_sections: number;
    total_stock: number;
    in_stock: number;
    low_stock: number;
    out_of_stock: number;
}
