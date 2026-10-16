export const dashboardPage = {
    title: 'Dashboard',

    description:
        "Here's an overview of what's happening in your workshop today.",

    greeting: 'Good morning, Admin',

    quickActionsTitle: 'Quick Actions',

    overviewTitle: "Today's Overview",

    productionTrendTitle: 'Production Trend',
    productionTrendDescription:
        'Production and rejection quantities over the selected period.',

    productionTrendPeriods: [
        {
            value: '7',
            label: 'Last 7 Days',
        },
        {
            value: '30',
            label: 'Last 30 Days',
        },
    ],

    machinePerformanceTitle: 'Machine Performance',
    machinePerformanceDescription: "Today's production activity by machine.",

    machineViewAllLabel: 'View all',

    attendanceTitle: "Today's Attendance",
    attendanceViewAllLabel: 'View all',

    attendanceStatuses: {
        present: 'Present',
        working: 'Working',
        onBreak: 'On Break',
        loggedOut: 'Logged Out',
        absent: 'Absent',
    },

    operatorPerformanceTitle: 'Operator Performance',
    operatorPerformanceDescription:
        "Today's production performance by operator.",

    inventoryAttentionTitle: 'Inventory Attention',

    recentActivityTitle: 'Recent Activity',

    table: {
        machine: {
            machine: 'Machine',
            operator: 'Operator',
            parts: 'Parts',
            quantity: 'Quantity',
            productionTime: 'Production Time',
        },

        operator: {
            operator: 'Operator',
            quantity: 'Quantity',
            rejected: 'Rejected',
            productionTime: 'Production Time',
            efficiency: 'Efficiency',
        },
    },

    empty: {
        machinePerformance: 'No machine activity found.',
        operatorPerformance: 'No operator activity found.',
        inventoryAttention: 'No inventory attention required.',
        recentActivity: 'No recent activity found.',
    },
} as const;
