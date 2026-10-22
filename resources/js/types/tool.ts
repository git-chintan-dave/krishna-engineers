import type { PaginatedResponse, SortDirection } from '@/types/listing';
import { ToolSchemaData } from '@/schemas/toolSchema';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

export type ToolType = 'endmill' | 'drill' | 'insert' | 'special_tools';

export type Tool = {
    id: number;
    uuid: string;
    name: string;
    type: ToolType;
    image: string | null;
    quantity: string;
    status: 'active' | 'inactive';
    created_at: string | null;
    updated_at: string | null;
};

export type ToolOption = {
    value: string;
    label: string;
};

export type ToolFormData = ToolSchemaData;

export type ToolSort = 'name' | 'type' | 'quantity' | 'status' | 'created_at';

export type ToolFilters = {
    search: string;
    type: ToolType | 'all';
    status: 'active' | 'inactive' | 'all';
    perPage: number;
    sort: ToolSort;
    direction: SortDirection;
};

export type ToolsResponse = PaginatedResponse<Tool>;

export type ToolTypeOptionsResponse = ToolOption[];
