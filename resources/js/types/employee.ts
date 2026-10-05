import type {
    PaginatedResponse,
    SortDirection,
    Status,
} from '@/types/listing';

export type EmployeeRole =
    | 'super_admin'
    | 'supervisor'
    | 'operator'
    | 'bandsaw'
    | 'deburring_packaging';

export type EmployeeShift =
    | 'day'
    | 'night'
    | '8_hr'
    | '8_hr_total';

export type EmployeeOption = {
    value: string;
    label: string;
};

export type Employee = {
    id: number;
    uuid: string;
    name: string;
    employee_code: string;
    phone_number: string | null;
    alternate_phone_number: string | null;
    email: string | null;
    joining_date: string | null;

    role: EmployeeRole;
    role_label: string;

    shift: EmployeeShift | null;
    shift_label: string | null;

    salary: string;
    incentive_per_day: string;
    deduction_per_day: string;

    status: Status;
    status_label: string;

    created_at: string | null;
    updated_at: string | null;
};

export type EmployeeFormData = {
    name: string;
    employee_code: string;
    phone_number: string;
    alternate_phone_number: string;
    email: string;
    joining_date: string;
    password: string;
    password_confirmation: string;

    role: EmployeeRole | null;
    shift: EmployeeShift | null;

    salary: number | null;
    incentive_per_day: number | null;
    deduction_per_day: number | null;

    status: boolean;
    aadhar_number: string;
};

export type EmployeeSort =
    | 'name'
    | 'employee_code'
    | 'phone_number'
    | 'email'
    | 'role'
    | 'shift'
    | 'salary'
    | 'incentive_per_day'
    | 'deduction_per_day'
    | 'status'
    | 'created_at';

export type EmployeeFilters = {
    search: string;
    role: EmployeeRole | 'all';
    shift: EmployeeShift | 'all';
    status: Status | 'all';
    perPage: number;
    sort: EmployeeSort;
    direction: SortDirection;
};

export type EmployeesResponse =
    PaginatedResponse<Employee>;

export type EmployeeOptionsResponse =
    EmployeeOption[];
