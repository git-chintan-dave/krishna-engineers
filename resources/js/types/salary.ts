import type {PaginatedResponse, SortDirection} from '@/types/listing';

export type SalaryStatus = 'draft' | 'paid';

export type SalaryEmployee = {
    id: number;
    name: string;
    employee_code: string;
};

export type Salary = {
    id: number;
    uuid: string;
    user_id: number;

    user: SalaryEmployee;

    salary_period_start: string;
    salary_period_end: string;

    salary_period_start_formatted: string;
    salary_period_end_formatted: string;

    base_salary: string;
    base_salary_formatted: string;
    working_days: number;

    upad: string;
    upad_formatted: string;
    paid_loan: string;
    paid_loan_formatted: string;

    incentive_rate: string;
    incentive: string;
    incentive_formatted: string;

    deduction_rate: string;
    deduction: string;
    deduction_formatted: string;

    adjustment: string;
    adjustment_formatted: string;
    adjustment_reason: string | null;

    final_payable: string;
    final_payable_formatted: string;

    status: SalaryStatus;
    status_label: string;

    paid_at: string | null;
    paid_at_formatted: string | null;

    created_at: string;
};

export type SalaryFormData = {
    user_id: number | string;

    salary_period_start: string;
    salary_period_end: string;

    upad: number | string;
    paid_loan: number | string;

    adjustment: number | string;
    adjustment_reason: string;
};

export type SalarySort =
    | 'salary_period_start'
    | 'salary_period_end'
    | 'base_salary'
    | 'working_days'
    | 'final_payable'
    | 'status'
    | 'created_at';

export type SalaryFilters = {
    user_id: number | 'all';
    status: SalaryStatus | 'all';
    start_date: string;
    end_date: string;
    perPage: number;
    sort: SalarySort;
    direction: SortDirection;
};

export type SalaryResponse = PaginatedResponse<Salary>;

export type SalaryEmployeeOption = {
    value: string;
    label: string;
};

export type SalaryStatusOption = {
    value: SalaryStatus;
    label: string;
};
