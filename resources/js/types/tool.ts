import type { PaginatedResponse, SortDirection } from '@/types/listing';

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

export type ToolFormData = {
    name: string;
    type: ToolType | null;
    image: File | null;
    remove_image: boolean;
    quantity?: number | null;
    status: boolean;
};

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
