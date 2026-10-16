import type {PaginatedResponse, SortDirection, Status, StatusFilter,} from '@/types/listing';
import {Section} from '@/types/section';

export type Part = {
    id: number;
    uuid: string;
    name: string;
    new_part_name: string | null;
    section_id: number;
    section: Section;
    image: string | null;
    cutting_size: string | null;
    part_weight: string | null;
    raw_material_weight: string | null;
    status: Status;
    created_at: string;
};

export type PartFormData = {
    name: string;
    new_part_name: string;
    section_id: number | null;
    image: File | null;
    remove_image: boolean;
    cutting_size: string;
    part_weight: string;
    raw_material_weight: string;
    status: boolean;
};

export type PartSort =
    | 'name'
    | 'new_part_name'
    | 'cutting_size'
    | 'part_weight'
    | 'raw_material_weight'
    | 'status'
    | 'created_at';

export type PartFilters = {
    search: string;
    sectionId: number | 'all';
    status: StatusFilter;
    perPage: number;
    sort: PartSort;
    direction: SortDirection;
};

export type PartsResponse = PaginatedResponse<Part>;
