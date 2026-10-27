export type DashboardTrend = 'up' | 'down' | 'neutral';

export interface DashboardStat {
    label: string;
    value: string;
    change: string;
    trend: DashboardTrend;
    description: string;
}

export interface DashboardProductionTrend {
    date: string;
    productionQuantity: number;
    rejectionQuantity: number;
}

export interface DashboardMachinePerformance {
    id: number;
    referenceName: string;
    name: string;
    operator: string;
    parts: number;
    quantity: number;
    productionTime: string;
}

export interface DashboardAttendanceSummary {
    label: string;
    value: number;
}

export interface DashboardOperatorPerformance {
    id: number;
    name: string;
    quantity: number;
    rejectionQuantity: number;
    productionTime: string;
    efficiency: number;
}

export interface DashboardInventoryAttention {
    id: number;
    type: 'low_stock' | 'tool_custody' | 'pending_return';
    title: string;
    description: string;
}

export interface DashboardActivity {
    id: number;
    description: string;
    time: string;
    type:
        | 'operator_work_log'
        | 'bandsaw_work_log'
        | 'tool'
        | 'attendance'
        | 'inventory';
}

export interface DashboardQuickAction {
    label: string;
    description: string;
    href: string;
    icon: string;
}

//
export interface TodayShift {
    status: 'present' | 'not_recorded';
    statusLabel: string;
    shiftLabel: string;
    checkIn: string | null;
    checkOut: string | null;
    totalWorkingSeconds: number;
    breakSeconds: number;
    effectiveSeconds: number;
    undertimeSeconds: number;
    targetSeconds: number;
    isComplete: boolean;
    breaks: {
        startAt: string | null;
        endAt: string | null;
        durationSeconds: number;
        isOngoing: boolean;
    }[];
}
