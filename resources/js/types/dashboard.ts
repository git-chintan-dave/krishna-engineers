export interface StockItem {
    id: number;
    name: string;
    detail: string;
    quantity: number;
}

export interface StockDashboard {
    threshold: number;
    total: number;
    healthy: number;
    low: number;
    out: number;
    items: StockItem[];
}

export interface SalaryEmployeePayment {
    id: number;
    name: string;
    amount: number;
    amountFormatted: string;
    status: 'paid' | 'draft';
    paidAt: string | null;
}

export interface SalaryBreakdown {
    baseSalary: number;
    baseSalaryFormatted: string;

    incentive: number;
    incentiveFormatted: string;

    deduction: number;
    deductionFormatted: string;

    upad: number;
    upadFormatted: string;

    paidLoan: number;
    paidLoanFormatted: string;

    adjustment: number;
    adjustmentFormatted: string;
}

export interface SalaryPeriod {
    label: string;
    startDate: string;
    endDate: string;

    totalPayable: number;
    totalPayableFormatted: string;

    paidAmount: number;
    paidAmountFormatted: string;

    pendingAmount: number;
    pendingAmountFormatted: string;

    employeeCount: number;
    paidEmployees: number;
    pendingEmployees: number;

    paymentPercentage: number;
    paymentPercentageFormatted: string;

    breakdown: SalaryBreakdown;

    employees: SalaryEmployeePayment[];
}

export interface SalaryDashboard {
    currentPeriod: SalaryPeriod | null;
    previousPeriod: SalaryPeriod | null;
}
