export type DashboardRole =
    | 'super_admin'
    | 'supervisor'
    | 'operator'
    | 'bandsaw'
    | 'deburring_packaging';

export type DashboardPeriod = 'today' | 'week' | 'month';

export type AttendanceStatus =
    | 'not_recorded'
    | 'on_shift'
    | 'on_break'
    | 'completed';

export interface DashboardStat {
    label: string;
    value: string | number;
    description: string;
}

export interface DashboardItem {
    title: string;
    subtitle?: string | null;
    meta?: string | null;
}

export interface DashboardSection {
    title: string;
    emptyMessage?: string;
    items: DashboardItem[];
}

export interface AttendanceBreakSummary {
    startAt: string | null;
    endAt: string | null;
    durationSeconds: number;
    isOngoing: boolean;
}

export interface TodayShiftSummary {
    status: AttendanceStatus;
    statusLabel: string;
    shiftLabel: string;
    checkIn: string | null;
    checkOut: string | null;
    totalSeconds: number;
    breakSeconds: number;
    effectiveSeconds: number;
    activeBreak: boolean;
    breaks: AttendanceBreakSummary[];
}

export interface MonthlyAttendanceSummary {
    presentDays: number;
    absentDays: number;
    recordedDays: number;
}

export interface TrendPoint {
    label: string;
    goodQuantity: number;
    rejectedQuantity: number;
    totalQuantity: number;
}

export interface ProductionPeriodSummary {
    goodQuantity: number;
    rejectedQuantity: number;
    totalQuantity: number;
    productionSeconds: number;
}

export interface AttendanceOverview {
    present: number;
    notRecorded: number;
    openShifts: number;
    totalEmployees: number;
}

export interface OperatorPerformanceItem {
    userId: number | string;
    name: string;
    employeeCode: string;
    goodQuantity: number;
    rejectedQuantity: number;
    totalQuantity: number;
    productionSeconds: number;
    effectiveWorkingSeconds: number;
    efficiency: number | null;
    rank: number | null;
}

export interface OperatorPerformance {
    today: OperatorPerformanceItem[];
    week: OperatorPerformanceItem[];
    month: OperatorPerformanceItem[];
}

export interface MachinePerformanceItem {
    machineId: number | string;
    machineName: string;
    goodQuantity: number;
    rejectedQuantity: number;
    totalQuantity: number;
    productionSeconds: number;
}

export interface PartPerformanceItem {
    partId: number | string;
    partName: string;
    goodQuantity: number;
    rejectedQuantity: number;
    totalQuantity: number;
}

export interface InventoryAlert {
    id: number | string;
    name: string;
    currentQuantity: number;
    threshold: number;
    type: 'tool' | 'raw_material';
}

export interface SalaryMonthlyTrend {
    label: string;
    paidAmount: number;
    pendingAmount: number;
}

export interface SalaryOverview {
    lastPaidMonth: string | null;
    lastPaidAmount: number;
    currentMonthEstimate: number;
    pendingAmount: number;
    pendingCount: number;
    paidAmount: number;
    paidCount: number;
    monthlyTrend: SalaryMonthlyTrend[];
}

export interface DashboardPayload {
    title: string;
    description: string;
    todayShift: TodayShiftSummary;
    monthlyAttendance: MonthlyAttendanceSummary;
    stats: DashboardStat[];
    productionOverview: ProductionOverview;
    attendanceOverview: AttendanceOverview;
    operatorPerformance: OperatorPerformance;
    machinePerformance: MachinePerformanceItem[];
    partPerformance: PartPerformanceItem[];
    inventoryAlerts: InventoryAlert[];
    salaryOverview?: SalaryOverview;
    sections: DashboardSection[];
}

export interface inventoryAlerts {
    id: string;
    type: 'raw_material' | 'tool';
    name: string;
    currentQuantity: number;
    threshold: number;
}

export type ProductionSummary = {
    totalQuantity: number;
    goodQuantity: number;
    rejectedQuantity: number;
    productionSeconds: number;
};

export type ProductionTrendPoint = {
    label: string;
    totalQuantity: number;
    goodQuantity: number;
    rejectedQuantity: number;
};

export type ProductionOverview = {
    today: ProductionSummary;
    week: ProductionSummary;
    month: ProductionSummary;
    dailyTrend: ProductionTrendPoint[];
};
