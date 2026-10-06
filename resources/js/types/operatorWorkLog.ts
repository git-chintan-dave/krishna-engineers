import type {PaginatedResponse, SortDirection,} from '@/types/listing';

export type OperatorWorkLog = {
    id: number;
    uuid: string;

    work_date: string;
    shift: string | null;

    user: {
        id: number;
        name: string;
        employee_code: string;
    };

    machine: {
        id: number;
        name: string;
        reference_name: string | null;
    };

    part: {
        id: number;
        name: string;
        reference_name: string;
    };

    setup: {
        id: number;
        name: string;
    };

    part_setup_id: number;

    machine_cycle_time: number;
    actual_cycle_time: number;

    quantity: number;
    rejection_quantity: number;

    production_seconds: number;
    production_seconds_formatted: string;

    remarks: string | null;

    created_at: string;
    updated_at: string;

};

export type OperatorWorkLogFormData = {
    user_id: number | null;

    work_date: string;
    shift: string | null;

    machine_id: number | null;
    part_id: number | null;
    setup_id: number | null;
    part_setup_id: number | null;

    machine_cycle_time: number;
    actual_cycle_time: number;

    quantity: number;
    rejection_quantity: number;

    remarks: string;
};

export type OperatorWorkLogPartSetupOption = {
    id: number;
    part_id: number;
    setup_id: number;
    machine_cycle_time_seconds: number;
    actual_cycle_time_seconds: number;
};

export type OperatorWorkLogSort =
    | 'work_date'
    | 'shift'
    | 'user_id'
    | 'machine_id'
    | 'part_id'
    | 'setup_id'
    | 'quantity'
    | 'created_at';

export type OperatorWorkLogFilters = {
    search: string;
    workDate: string;
    shift: string;
    userId: number | string;
    machineId: number | string;
    partId: number | string;
    setupId: number | string;
    perPage: number;
    sort: OperatorWorkLogSort;
    direction: SortDirection;
};

export type OperatorWorkLogsResponse =
    PaginatedResponse<OperatorWorkLog>;
