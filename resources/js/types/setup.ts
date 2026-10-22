import type {
    PaginatedResponse,
    SortDirection,
    Status,
    StatusFilter,
} from '@/types/listing';
import { SetupSchemaData } from '@/schemas/setupSchema';

export type Setup = {
    id: number;
    uuid: string;
    name: string;
    status: Status;
    created_at: string;
};

export type SetupFormData = SetupSchemaData;

export type SetupSort = 'id' | 'name' | 'status' | 'created_at';

export type SetupFilters = {
    search: string;
    status: StatusFilter;
    perPage: number;
    sort: SetupSort;
    direction: SortDirection;
};

export type SetupsResponse = PaginatedResponse<Setup>;
