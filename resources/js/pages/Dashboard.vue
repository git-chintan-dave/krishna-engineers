<script setup lang="ts">
import {
    ArrowDown,
    ArrowUp,
    ArrowUpRight,
    ClipboardList,
    Clock3,
    PackagePlus,
    Scissors,
    Users,
    Wrench,
} from '@lucide/vue';
import { computed, ref } from 'vue';

import AppSelect from '@/components/common/AppSelect.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboardPage } from '@/config/dashboard';
import { dashboard } from '@/routes';
import type {
    DashboardActivity,
    DashboardAttendanceSummary,
    DashboardInventoryAttention,
    DashboardMachinePerformance,
    DashboardOperatorPerformance,
    DashboardProductionTrend,
    DashboardQuickAction,
    DashboardStat,
} from '@/types/dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

// Data

const dashboardStats: DashboardStat[] = [
    {
        label: 'Operators Present',
        value: '18',
        change: '+2',
        trend: 'up',
        description: 'vs yesterday',
    },
    {
        label: 'Machines Active',
        value: '5',
        change: '+1',
        trend: 'up',
        description: 'vs yesterday',
    },
    {
        label: 'Production Quantity',
        value: '1,248',
        change: '+8.4%',
        trend: 'up',
        description: 'vs yesterday',
    },
    {
        label: 'Rejected Quantity',
        value: '12',
        change: '-14.3%',
        trend: 'down',
        description: 'vs yesterday',
    },
    {
        label: 'Bandsaw Quantity',
        value: '340',
        change: '+5.6%',
        trend: 'up',
        description: 'vs yesterday',
    },
    {
        label: 'Production Time',
        value: '42:18:30',
        change: '+6.2%',
        trend: 'up',
        description: 'vs yesterday',
    },
];

const dashboardQuickActions: DashboardQuickAction[] = [
    {
        label: 'Operator Work Log',
        description: 'Record operator production',
        href: '/work-logs/operator/create',
        icon: 'ClipboardList',
    },
    {
        label: 'Bandsaw Work Log',
        description: 'Record bandsaw production',
        href: '/work-logs/bandsaw/create',
        icon: 'Scissors',
    },
    {
        label: 'Add Purchase',
        description: 'Record material purchase',
        href: '#',
        icon: 'PackagePlus',
    },
    {
        label: 'Issue Tool',
        description: 'Issue a tool to staff',
        href: '#',
        icon: 'Wrench',
    },
];

const dashboardProductionTrend: DashboardProductionTrend[] = [
    {
        date: 'Sep 22',
        productionQuantity: 980,
        rejectionQuantity: 18,
    },
    {
        date: 'Sep 23',
        productionQuantity: 1120,
        rejectionQuantity: 14,
    },
    {
        date: 'Sep 24',
        productionQuantity: 1050,
        rejectionQuantity: 16,
    },
    {
        date: 'Sep 25',
        productionQuantity: 1185,
        rejectionQuantity: 11,
    },
    {
        date: 'Sep 26',
        productionQuantity: 1090,
        rejectionQuantity: 15,
    },
    {
        date: 'Sep 27',
        productionQuantity: 1155,
        rejectionQuantity: 14,
    },
    {
        date: 'Sep 28',
        productionQuantity: 1248,
        rejectionQuantity: 12,
    },
];

const dashboardMachinePerformance: DashboardMachinePerformance[] = [
    {
        id: 1,
        referenceName: 'M-001',
        name: 'CNC Turning Machine',
        operator: 'Operator One',
        parts: 3,
        quantity: 320,
        productionTime: '06:12:30',
    },
    {
        id: 2,
        referenceName: 'M-002',
        name: 'CNC Milling Machine',
        operator: 'Operator Two',
        parts: 2,
        quantity: 285,
        productionTime: '05:48:20',
    },
    {
        id: 3,
        referenceName: 'M-003',
        name: 'CNC Lathe Machine',
        operator: 'Operator Three',
        parts: 4,
        quantity: 246,
        productionTime: '04:52:15',
    },
    {
        id: 4,
        referenceName: 'M-004',
        name: 'CNC Machining Center',
        operator: 'Operator Four',
        parts: 2,
        quantity: 218,
        productionTime: '03:45:40',
    },
    {
        id: 5,
        referenceName: 'M-005',
        name: 'CNC Production Machine',
        operator: 'Operator Five',
        parts: 3,
        quantity: 179,
        productionTime: '03:21:10',
    },
];

const dashboardAttendanceSummary: DashboardAttendanceSummary[] = [
    {
        label: 'Present',
        value: 18,
    },
    {
        label: 'Working',
        value: 14,
    },
    {
        label: 'On Break',
        value: 2,
    },
    {
        label: 'Logged Out',
        value: 2,
    },
    {
        label: 'Absent',
        value: 3,
    },
];

const dashboardOperatorPerformance: DashboardOperatorPerformance[] = [
    {
        id: 1,
        name: 'Operator One',
        quantity: 320,
        rejectionQuantity: 4,
        productionTime: '06:12:30',
        efficiency: 91.4,
    },
    {
        id: 2,
        name: 'Operator Two',
        quantity: 285,
        rejectionQuantity: 2,
        productionTime: '05:48:20',
        efficiency: 87.2,
    },
    {
        id: 3,
        name: 'Operator Three',
        quantity: 246,
        rejectionQuantity: 3,
        productionTime: '04:52:15',
        efficiency: 84.8,
    },
    {
        id: 4,
        name: 'Operator Four',
        quantity: 218,
        rejectionQuantity: 1,
        productionTime: '03:45:40',
        efficiency: 89.6,
    },
    {
        id: 5,
        name: 'Operator Five',
        quantity: 179,
        rejectionQuantity: 2,
        productionTime: '03:21:10',
        efficiency: 82.3,
    },
];

const dashboardInventoryAttention: DashboardInventoryAttention[] = [
    {
        id: 1,
        type: 'low_stock',
        title: 'Section 12000 - 127*21',
        description: 'Only 3 sheets remaining in stock.',
    },
    {
        id: 2,
        type: 'tool_custody',
        title: 'Tools with operators',
        description: '2 tools are currently assigned to operators.',
    },
    {
        id: 3,
        type: 'pending_return',
        title: 'Tool return pending',
        description: '1 tool is currently with a supervisor.',
    },
];

const dashboardRecentActivity: DashboardActivity[] = [
    {
        id: 1,
        description: 'Operator One recorded 25 × 2I-2003 on M-001.',
        time: '10:42 AM',
        type: 'operator_work_log',
    },
    {
        id: 2,
        description: 'Bandsaw Operator recorded 10 × 2I-2003.',
        time: '10:21 AM',
        type: 'bandsaw_work_log',
    },
    {
        id: 3,
        description: 'End Mill #END-004 was issued to Operator Two.',
        time: '9:55 AM',
        type: 'tool',
    },
    {
        id: 4,
        description: 'Operator Three resumed work after break.',
        time: '9:30 AM',
        type: 'attendance',
    },
    {
        id: 5,
        description:
            '5 sheets of Section 12000 - 127*25 were added to inventory.',
        time: '9:12 AM',
        type: 'inventory',
    },
];

// State

const selectedPeriod = ref('7');

const periodOptions = dashboardPage.productionTrendPeriods;

// Icons

const statIcons = [Users, Wrench, ClipboardList, ArrowDown, Scissors, Clock3];

const quickActionIcons: Record<string, typeof ClipboardList> = {
    ClipboardList,
    Scissors,
    PackagePlus,
    Wrench,
};

// Computed

const attendanceItems = computed(() =>
    dashboardAttendanceSummary.map((item) => ({
        ...item,
        label:
            dashboardPage.attendanceStatuses[
                item.label.toLowerCase() as keyof typeof dashboardPage.attendanceStatuses
            ] ?? item.label,
    })),
);

const productionTrend = computed(() => {
    if (selectedPeriod.value === '30') {
        return dashboardProductionTrend;
    }

    return dashboardProductionTrend.slice(-7);
});

// Helpers

const getActivityIcon = (type: string) => {
    switch (type) {
        case 'operator_work_log':
            return ClipboardList;

        case 'bandsaw_work_log':
            return Scissors;

        case 'tool':
            return Wrench;

        case 'attendance':
            return Clock3;

        default:
            return PackagePlus;
    }
};
</script>

<template>
    <div class="w-full space-y-6 p-4 md:p-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
        >
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ dashboardPage.greeting }}
                </h1>

                <p class="text-sm text-muted-foreground">
                    {{ dashboardPage.description }}
                </p>
            </div>
        </div>

        <!-- Quick Actions -->
        <section class="space-y-4">
            <div>
                <h2 class="text-base font-semibold">
                    {{ dashboardPage.quickActionsTitle }}
                </h2>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Button
                    v-for="action in dashboardQuickActions"
                    :key="action.label"
                    variant="outline"
                    class="h-auto justify-start gap-3 px-4 py-3 text-left"
                    as-child
                >
                    <a :href="action.href">
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted"
                        >
                            <component
                                :is="quickActionIcons[action.icon]"
                                class="size-4"
                            />
                        </span>

                        <span class="min-w-0">
                            <span class="block truncate font-medium">
                                {{ action.label }}
                            </span>

                            <span
                                class="block truncate text-xs font-normal text-muted-foreground"
                            >
                                {{ action.description }}
                            </span>
                        </span>
                    </a>
                </Button>
            </div>
        </section>

        <!-- Overview -->
        <section class="space-y-4">
            <div>
                <h2 class="text-base font-semibold">
                    {{ dashboardPage.overviewTitle }}
                </h2>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card v-for="(stat, index) in dashboardStats" :key="stat.label">
                    <CardContent class="p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-2">
                                <p class="text-sm text-muted-foreground">
                                    {{ stat.label }}
                                </p>

                                <p
                                    class="text-2xl font-semibold tracking-tight"
                                >
                                    {{ stat.value }}
                                </p>
                            </div>

                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted"
                            >
                                <component
                                    :is="statIcons[index]"
                                    class="size-4 text-muted-foreground"
                                />
                            </div>
                        </div>

                        <div class="mt-4 flex items-center gap-1.5 text-xs">
                            <span
                                class="inline-flex items-center gap-0.5 font-medium"
                                :class="
                                    stat.trend === 'up'
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : stat.trend === 'down'
                                          ? 'text-rose-600 dark:text-rose-400'
                                          : 'text-muted-foreground'
                                "
                            >
                                <ArrowUp
                                    v-if="stat.trend === 'up'"
                                    class="size-3"
                                />

                                <ArrowDown
                                    v-else-if="stat.trend === 'down'"
                                    class="size-3"
                                />

                                {{ stat.change }}
                            </span>

                            <span class="text-muted-foreground">
                                {{ stat.description }}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </section>

        <!-- Production Trend -->
        <section>
            <Card>
                <CardHeader
                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="space-y-1">
                        <CardTitle>
                            {{ dashboardPage.productionTrendTitle }}
                        </CardTitle>

                        <p class="text-sm text-muted-foreground">
                            {{ dashboardPage.productionTrendDescription }}
                        </p>
                    </div>

                    <AppSelect
                        v-model="selectedPeriod"
                        :options="periodOptions"
                        class="w-full sm:w-36"
                    />
                </CardHeader>

                <CardContent>
                    <div
                        class="flex h-72 items-end gap-2 overflow-x-auto border-b border-l px-4 pt-8 pb-0"
                    >
                        <div
                            v-for="item in productionTrend"
                            :key="item.date"
                            class="flex min-w-20 flex-1 items-end gap-1"
                        >
                            <div
                                class="flex flex-1 flex-col items-center gap-2"
                            >
                                <div
                                    class="w-full rounded-t-md bg-primary/80"
                                    :style="{
                                        height: `${Math.max(
                                            (item.productionQuantity / 1300) *
                                                210,
                                            12,
                                        )}px`,
                                    }"
                                    :title="`Production: ${item.productionQuantity}`"
                                />

                                <span class="text-xs text-muted-foreground">
                                    {{ item.date }}
                                </span>
                            </div>

                            <div
                                class="flex flex-1 flex-col items-center gap-2"
                            >
                                <div
                                    class="w-full rounded-t-md bg-muted-foreground/40"
                                    :style="{
                                        height: `${Math.max(
                                            (item.rejectionQuantity / 20) * 210,
                                            8,
                                        )}px`,
                                    }"
                                    :title="`Rejected: ${item.rejectionQuantity}`"
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex items-center justify-center gap-6 text-xs text-muted-foreground"
                    >
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-full bg-primary" />
                            Production
                        </div>

                        <div class="flex items-center gap-2">
                            <span
                                class="size-2.5 rounded-full bg-muted-foreground/40"
                            />
                            Rejected
                        </div>
                    </div>
                </CardContent>
            </Card>
        </section>

        <!-- Machine + Attendance -->
        <section
            class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,1fr)]"
        >
            <!-- Machine Performance -->
            <Card>
                <CardHeader
                    class="flex flex-row items-start justify-between gap-4"
                >
                    <div class="space-y-1">
                        <CardTitle>
                            {{ dashboardPage.machinePerformanceTitle }}
                        </CardTitle>

                        <p class="text-sm text-muted-foreground">
                            {{ dashboardPage.machinePerformanceDescription }}
                        </p>
                    </div>

                    <Button variant="ghost" size="sm" class="shrink-0">
                        {{ dashboardPage.machineViewAllLabel }}
                        <ArrowUpRight class="ml-1 size-4" />
                    </Button>
                </CardHeader>

                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[620px] text-sm">
                            <thead>
                                <tr
                                    class="border-b text-left text-xs text-muted-foreground"
                                >
                                    <th class="pb-3 font-medium">
                                        {{
                                            dashboardPage.table.machine.machine
                                        }}
                                    </th>

                                    <th class="pb-3 font-medium">
                                        {{
                                            dashboardPage.table.machine.operator
                                        }}
                                    </th>

                                    <th class="pb-3 text-right font-medium">
                                        {{ dashboardPage.table.machine.parts }}
                                    </th>

                                    <th class="pb-3 text-right font-medium">
                                        {{
                                            dashboardPage.table.machine.quantity
                                        }}
                                    </th>

                                    <th class="pb-3 text-right font-medium">
                                        {{
                                            dashboardPage.table.machine
                                                .productionTime
                                        }}
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="machine in dashboardMachinePerformance"
                                    :key="machine.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-3">
                                        <div>
                                            <p class="font-medium">
                                                {{ machine.referenceName }}
                                            </p>

                                            <p
                                                class="max-w-48 truncate text-xs text-muted-foreground"
                                            >
                                                {{ machine.name }}
                                            </p>
                                        </div>
                                    </td>

                                    <td class="py-3 text-muted-foreground">
                                        {{ machine.operator }}
                                    </td>

                                    <td class="py-3 text-right tabular-nums">
                                        {{ machine.parts }}
                                    </td>

                                    <td
                                        class="py-3 text-right font-medium tabular-nums"
                                    >
                                        {{ machine.quantity }}
                                    </td>

                                    <td class="py-3 text-right tabular-nums">
                                        {{ machine.productionTime }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Attendance -->
            <Card>
                <CardHeader
                    class="flex flex-row items-start justify-between gap-4"
                >
                    <CardTitle>
                        {{ dashboardPage.attendanceTitle }}
                    </CardTitle>

                    <Button variant="ghost" size="sm" class="shrink-0">
                        {{ dashboardPage.attendanceViewAllLabel }}
                        <ArrowUpRight class="ml-1 size-4" />
                    </Button>
                </CardHeader>

                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="item in attendanceItems"
                            :key="item.label"
                            class="flex items-center justify-between"
                        >
                            <span class="text-sm text-muted-foreground">
                                {{ item.label }}
                            </span>

                            <span class="text-sm font-semibold tabular-nums">
                                {{ item.value }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </section>

        <!-- Operator Performance -->
        <section>
            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ dashboardPage.operatorPerformanceTitle }}
                    </CardTitle>

                    <p class="text-sm text-muted-foreground">
                        {{ dashboardPage.operatorPerformanceDescription }}
                    </p>
                </CardHeader>

                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-sm">
                            <thead>
                                <tr
                                    class="border-b text-left text-xs text-muted-foreground"
                                >
                                    <th class="pb-3 font-medium">
                                        {{
                                            dashboardPage.table.operator
                                                .operator
                                        }}
                                    </th>

                                    <th class="pb-3 text-right font-medium">
                                        {{
                                            dashboardPage.table.operator
                                                .quantity
                                        }}
                                    </th>

                                    <th class="pb-3 text-right font-medium">
                                        {{
                                            dashboardPage.table.operator
                                                .rejected
                                        }}
                                    </th>

                                    <th class="pb-3 text-right font-medium">
                                        {{
                                            dashboardPage.table.operator
                                                .productionTime
                                        }}
                                    </th>

                                    <th class="pb-3 text-right font-medium">
                                        {{
                                            dashboardPage.table.operator
                                                .efficiency
                                        }}
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="operator in dashboardOperatorPerformance"
                                    :key="operator.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-3 font-medium">
                                        {{ operator.name }}
                                    </td>

                                    <td class="py-3 text-right tabular-nums">
                                        {{ operator.quantity }}
                                    </td>

                                    <td class="py-3 text-right tabular-nums">
                                        {{ operator.rejectionQuantity }}
                                    </td>

                                    <td class="py-3 text-right tabular-nums">
                                        {{ operator.productionTime }}
                                    </td>

                                    <td
                                        class="py-3 text-right font-medium tabular-nums"
                                    >
                                        {{ operator.efficiency }}%
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </section>

        <!-- Inventory + Activity -->
        <section class="grid gap-6 xl:grid-cols-2">
            <!-- Inventory Attention -->
            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ dashboardPage.inventoryAttentionTitle }}
                    </CardTitle>
                </CardHeader>

                <CardContent>
                    <div
                        v-if="dashboardInventoryAttention.length"
                        class="space-y-4"
                    >
                        <div
                            v-for="item in dashboardInventoryAttention"
                            :key="item.id"
                            class="flex gap-3 rounded-lg border p-3"
                        >
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted"
                            >
                                <PackagePlus class="size-4" />
                            </div>

                            <div class="min-w-0">
                                <p class="text-sm font-medium">
                                    {{ item.title }}
                                </p>

                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ item.description }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        {{ dashboardPage.empty.inventoryAttention }}
                    </p>
                </CardContent>
            </Card>

            <!-- Recent Activity -->
            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ dashboardPage.recentActivityTitle }}
                    </CardTitle>
                </CardHeader>

                <CardContent>
                    <div
                        v-if="dashboardRecentActivity.length"
                        class="space-y-5"
                    >
                        <div
                            v-for="activity in dashboardRecentActivity"
                            :key="activity.id"
                            class="flex gap-3"
                        >
                            <div
                                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted"
                            >
                                <component
                                    :is="getActivityIcon(activity.type)"
                                    class="size-3.5"
                                />
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm leading-5">
                                    {{ activity.description }}
                                </p>

                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ activity.time }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        {{ dashboardPage.empty.recentActivity }}
                    </p>
                </CardContent>
            </Card>
        </section>
    </div>
</template>
