import type {
    PaginatedResponse,
    SortDirection,
    Status,
    StatusFilter,
} from '@/types/listing';
import { SectionSchemaData } from '@/schemas/sectionSchema';

export type Section = {
    id: number;
    uuid: string;
    name: string;
    status: Status;
    created_at: string | null;
    updated_at: string | null;
};

export type SectionFormData = SectionSchemaData;

export type SectionFilters = {
    search: string;
    status: StatusFilter;
    perPage: number;
    sort: SectionSort;
    direction: SortDirection;
};

export type SectionSort = 'name' | 'created_at';

export type SectionsResponse = PaginatedResponse<Section>;
