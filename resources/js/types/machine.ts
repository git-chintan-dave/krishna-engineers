import type {
    PaginatedResponse,
    SortDirection,
    Status,
    StatusFilter,
} from '@/types/listing';
import type { MachineSchemaData } from '@/schemas/machineSchema';

export type Machine = {
    id: number;
    uuid: string;
    name: string;
    machine_number: string | null;
    status: Status;
    created_at: string;
};

export type MachineFormData = MachineSchemaData;

export type MachineSort =
    | 'id'
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
