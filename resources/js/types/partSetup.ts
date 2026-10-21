import type {PaginatedResponse, SortDirection} from '@/types/listing';
import {Setup} from '@/types/setup';
import {PartSetupSchemaData} from "@/schemas/partSetupSchema";

export type PartSetupPart = {
    id: number;
    name: string;
    reference_name: string | null;
};

export type PartSetupSetup = {
    id: number;
    name: string;
};

export type PartSetupOption = {
    id: number;
    name: string;
    reference_name?: string | null;
};

export type PartSetup = {
    id: number;
    uuid: string;
    part_id: number;
    part: PartSetupPart;
    setup_id: number;
    setup: Setup;
    image: string | null;
    remove_image: boolean;

    machine_cycle_time: number;
    machine_cycle_time_formatted: string;

    actual_cycle_time: number;
    actual_cycle_time_formatted: string;

    created_at: string;
};

export type PartSetupFormData = PartSetupSchemaData;

export type PartSetupSort =
    | 'machine_cycle_time_seconds'
    | 'actual_cycle_time_seconds'
    | 'created_at';

export type PartSetupFilters = {
    search: string;
    partId: number | null;
    setupId: number | null;
    perPage: number;
    sort: PartSetupSort;
    direction: SortDirection;
};

export type PartSetupsResponse = PaginatedResponse<PartSetup>;
