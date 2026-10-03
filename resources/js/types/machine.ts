import type {
    PaginatedResponse,
    SortDirection,
    Status,
    StatusFilter,
} from '@/types/listing';

export type Machine = {
    id: number;
    uuid: string;
    name: string;
    machine_number: string | null;
    status: Status;
    created_at: string;
};

export type MachineFormData = {
    name: string;
    machine_number: string;
    status: boolean;
};

export type MachineSort =
    | 'name'
    | 'machine_number'
    | 'status'
    | 'created_at';

export type MachineFilters = {
    search: string;
    status: StatusFilter;
    perPage: number;
    sort: MachineSort;
    direction: SortDirection;
};

export type MachinesResponse = PaginatedResponse<Machine>;
