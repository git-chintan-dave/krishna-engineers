import type { PaginatedResponse, SortDirection } from '@/types/listing';

/*
|--------------------------------------------------------------------------
| Attendance
|--------------------------------------------------------------------------
*/

export type Attendance = {
    id: number;
    uuid: string;
    user_id: number;
    user: AttendanceEmployee;

    attendance_date: string;
    attendance_date_formatted: string;

    start_at: string;
    end_at: string | null;
    start_at_formatted: string;
    end_at_formatted: string | null;

    is_open: boolean;
    is_locked: boolean;

    total_working_seconds: number;
    total_working_seconds_formatted: string;
    break_seconds: number;
    break_seconds_formatted: string;
    effective_working_seconds: number;
    effective_working_seconds_formatted: string;

    production_seconds: number;
    production_seconds_formatted: string;
    production_efficiency: number | null;

    breaks: AttendanceBreak[];

    created_at: string;
};

/*
|--------------------------------------------------------------------------
| Employee
|--------------------------------------------------------------------------
*/

export type AttendanceEmployee = {
    id: number;
    name: string;
    employee_code: string;
};

/*
|--------------------------------------------------------------------------
| Break
|--------------------------------------------------------------------------
*/

export type AttendanceBreak = {
    id: number;
    start_at: string;
    end_at: string | null;
    start_at_formatted: string;
    end_at_formatted: string | null;
    duration_seconds: number;
    is_open: boolean;
};

export type AttendanceFormBreak = {
    uuid?: string;
    start_at: string;
    end_at: string;
};

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

export type AttendanceFormData = {
    user_id: number | string;
    attendance_date: string;
    start_at: string;
    end_at: string | null;
    breaks: AttendanceFormBreak[];
};

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

export type AttendanceSort =
    | 'attendance_date'
    | 'start_at'
    | 'end_at'
    | 'created_at';

export type AttendanceFilters = {
    userId: number | 'all';
    startDate: string;
    endDate: string;
    perPage: number;
    sort: AttendanceSort;
    direction: SortDirection;
};

/*
|--------------------------------------------------------------------------
| Responses
|--------------------------------------------------------------------------
*/

export type AttendanceResponse = PaginatedResponse<Attendance>;

/*
|--------------------------------------------------------------------------
| Select Options
|--------------------------------------------------------------------------
*/

export type AttendanceEmployeeOption = {
    value: string;
    label: string;
};


export interface AttendanceStatistics {
    total: number;
    today: number;
    currently_working: number;
    completed: number;
    unique_employees: number;
    total_working_seconds: number;
    total_effective_working_seconds: number;
    total_break_seconds: number;
    total_undertime_seconds: number;
}
