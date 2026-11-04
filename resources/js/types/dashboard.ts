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

export type AttendanceStatus = 'present' | 'leave' | 'incomplete';
export type EmployeeTodayStatus = AttendanceStatus | 'weekly_off';

export interface ManagementAttendanceEmployee {
    id: number;
    name: string;
    employeeCode: string;
    role: string;
    shift: string;
    status: AttendanceStatus;
    startAt: string | null;
    endAt: string | null;
    workingSeconds: number;
    workingFormatted: string;
    breakSeconds: number;
    breakFormatted: string;
    effectiveWorkingSeconds: number;
    effectiveWorkingFormatted: string;
    undertimeSeconds: number;
    undertimeFormatted: string;
}

export interface ManagementAttendancePeriod {
    date: string;
    label: string;
    isWorkingDay: boolean;
    totalEmployees: number;
    present: number;
    leave: number;
    incomplete: number;
    attendancePercentage: number;
    attendancePercentageFormatted: string;
    averageWorkingSeconds: number;
    averageWorkingFormatted: string;
    averageBreakSeconds: number;
    averageBreakFormatted: string;
    totalUndertimeSeconds: number;
    totalUndertimeFormatted: string;
    employees: ManagementAttendanceEmployee[];
}

export interface ManagementAttendanceDashboard {
    today: ManagementAttendancePeriod;
    yesterday: ManagementAttendancePeriod;
}

export interface EmployeeAttendanceToday {
    date: string;
    status: EmployeeTodayStatus;
    statusLabel: string;
    startAt: string;
    endAt: string;
    workingSeconds: number;
    workingFormatted: string;
    breakSeconds: number;
    breakFormatted: string;
    effectiveWorkingSeconds: number;
    effectiveWorkingFormatted: string;
    isWorking: boolean;
}

export interface EmployeeAttendanceRecord {
    date: string;
    day: string;
    clockIn: string;
    clockOut: string;
    effectiveWorkingSeconds: number;
    effectiveWorkingFormatted: string;
    breakSeconds: number;
    breakFormatted: string;
    undertimeSeconds: number;
    undertimeFormatted: string;
    status: AttendanceStatus;
    statusLabel: string;
}

export interface EmployeeAttendancePeriod {
    label: string;
    workingDays: number;
    presentDays: number;
    absentDays: number;
    incompleteDays: number;
    attendedDays: number;
    attendanceRate: number;
    attendanceRateFormatted: string;
    totalUndertimeSeconds: number;
    totalUndertimeFormatted: string;
    records: EmployeeAttendanceRecord[];
}

export interface EmployeeAttendanceDashboard {
    today: EmployeeAttendanceToday;
    currentPeriod: EmployeeAttendancePeriod;
    previousPeriod: EmployeeAttendancePeriod;
}

export interface ProductionMachine {
    name: string;
    quantity: number;
    quantityFormatted: string;
    rejected: number;
    rejectedFormatted: string;
}

export interface ProductionSectionSize {
    name: string;
    quantity: number;
    quantityFormatted: string;
}

export interface DailyProduction {
    date: string;
    workDate: string;
    operatorQuantity: number;
    operatorQuantityFormatted: string;
    operatorRejected: number;
    operatorRejectedFormatted: string;
    bandsawQuantity: number;
    bandsawQuantityFormatted: string;
}

export interface OperatorProductionSummary {
    totalQuantity: number;
    totalQuantityFormatted: string;
    goodQuantity: number;
    goodQuantityFormatted: string;
    rejectionQuantity: number;
    rejectionQuantityFormatted: string;
    productionSeconds: number;
    productionTimeFormatted: string;
    effectiveWorkingSeconds: number | null;
    effectiveWorkingTimeFormatted: string | null;
    productionEfficiency: string | null;
    rejectionRate: number;
    rejectionRateFormatted: string;
    goodRate: number;
    goodRateFormatted: string;
    workEntries: number;
    machineCount: number;
    partCount: number;
    machines: ProductionMachine[];
}

export interface BandsawProductionSummary {
    totalQuantity: number;
    totalQuantityFormatted: string;
    workEntries: number;
    partCount: number;
    sectionSizeCount: number;
    sectionSizes: ProductionSectionSize[];
}

export interface ProductionPeriod {
    label: string;
    operator: OperatorProductionSummary;
    bandsaw: BandsawProductionSummary;
    daily: DailyProduction[];
}

export interface ProductionDashboard {
    currentPeriod: ProductionPeriod;
    previousPeriod: ProductionPeriod;
}
