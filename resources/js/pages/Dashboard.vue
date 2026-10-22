<script lang="ts" setup>
import {
    ArrowDown,
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

type DashboardStat = {
    label: string;
    value: string;
    description: string;
};

type DashboardQuickAction = {
    label: string;
    description: string;
    href: string;
    icon: string;
};

type DashboardProductionTrend = {
    date: string;
    label: string;
    productionQuantity: number;
    rejectionQuantity: number;
};

type DashboardMachinePerformance = {
    id: number;
    referenceName: string;
    name: string;
    parts: number;
    quantity: number;
    productionTime: string;
};

type DashboardAttendanceSummary = {
    label: string;
    value: number;
};

type DashboardOperatorPerformance = {
    id: number;
    name: string;
    quantity: number;
    rejectionQuantity: number;
    productionTime: string;
    effectiveWorkingSeconds: number | null;
    efficiency: number | null;
};

type DashboardActivity = {
    id: number;
    description: string;
    time: string;
    type: string;
};

const props = defineProps<{
    role: string;
    stats: DashboardStat[];
    quickActions: DashboardQuickAction[];
    productionTrend: DashboardProductionTrend[];
    machinePerformance: DashboardMachinePerformance[];
    attendanceSummary: DashboardAttendanceSummary[];
    operatorPerformance: DashboardOperatorPerformance[];
    recentActivity: DashboardActivity[];
}>();

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

const selectedPeriod = ref('7');
const periodOptions = dashboardPage.productionTrendPeriods;

const isSuperAdmin = computed(() => props.role === 'super_admin');
const isSupervisor = computed(() => props.role === 'supervisor');
const isOperator = computed(() => props.role === 'operator');

const canViewOperations = computed(
    () => isSuperAdmin.value || isSupervisor.value,
);

const showProductionTrend = computed(
    () => props.role !== 'bandsaw' && props.productionTrend.length > 0,
);

const productionTrend = computed(() =>
    props.productionTrend.slice(-Number(selectedPeriod.value)),
);

const maxProductionQuantity = computed(() =>
    Math.max(
        1,
        ...productionTrend.value.map((item) => item.productionQuantity),
    ),
);

const maxRejectionQuantity = computed(() =>
    Math.max(1, ...productionTrend.value.map((item) => item.rejectionQuantity)),
);

const attendanceItems = computed(() =>
    props.attendanceSummary.map((item) => ({
        ...item,
        label:
            dashboardPage.attendanceStatuses[
                item.label.toLowerCase() as keyof typeof dashboardPage.attendanceStatuses
            ] ?? item.label,
    })),
);

const statIcons: Record<string, typeof ClipboardList> = {
    'Operators Present': Users,
    'Operators Absent': Users,
    'Machines Used': Wrench,
    'Production Quantity': ClipboardList,
    'Rejected Quantity': ArrowDown,
    'Production Time': Clock3,
    'Work Logs': ClipboardList,
    'Effective Hours': Clock3,
    Attendance: Users,
};

const quickActionIcons: Record<string, typeof ClipboardList> = {
    ClipboardList,
    Scissors,
    PackagePlus,
    Wrench,
};

const getActivityIcon = (type: string) => {
    switch (type) {
        case 'operator_work_log':
            return ClipboardList;
        case 'bandsaw_work_log':
            return Scissors;
        case 'attendance':
            return Clock3;
        case 'tool':
            return Wrench;
        default:
            return PackagePlus;
    }
};

const formatNumber = (value: number): string =>
    new Intl.NumberFormat().format(value);

const getProductionHeight = (quantity: number): string =>
    `${Math.max((quantity / maxProductionQuantity.value) * 190, 4)}px`;

const getRejectionHeight = (quantity: number): string =>
    `${Math.max((quantity / maxRejectionQuantity.value) * 70, 4)}px`;
</script>

<template>
    <div class="w-full space-y-6 p-4 md:p-6">
        <!-- Header -->
        <section class="space-y-1">
            <h1 class="text-2xl font-semibold tracking-tight">
                {{ dashboardPage.greeting }}
            </h1>

            <p class="text-sm text-muted-foreground">
                {{ dashboardPage.description }}
            </p>

            <p
                v-if="isSuperAdmin"
                class="text-xs font-medium text-muted-foreground"
            >
                Full production and workforce overview
            </p>

            <p
                v-else-if="isSupervisor"
                class="text-xs font-medium text-muted-foreground"
            >
                Production and workforce monitoring
            </p>

            <p
                v-else-if="isOperator"
                class="text-xs font-medium text-muted-foreground"
            >
                Your production and attendance overview
            </p>
        </section>

        <!-- Quick Actions -->
        <section v-if="quickActions.length" class="space-y-4">
            <h2 class="text-base font-semibold">
                {{ dashboardPage.quickActionsTitle }}
            </h2>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Button
                    v-for="action in quickActions"
                    :key="action.href"
                    as-child
                    class="h-auto justify-start gap-3 px-4 py-3 text-left"
                    variant="outline"
                >
                    <a :href="action.href">
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted"
                        >
                            <component
                                :is="
                                    quickActionIcons[action.icon] ??
                                    ClipboardList
                                "
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
        <section v-if="stats.length" class="space-y-4">
            <h2 class="text-base font-semibold">
                {{ dashboardPage.overviewTitle }}
            </h2>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card v-for="stat in stats" :key="stat.label">
                    <CardContent class="p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 space-y-2">
                                <p class="text-sm text-muted-foreground">
                                    {{ stat.label }}
                                </p>

                                <p
                                    class="text-2xl font-semibold tracking-tight break-words"
                                >
                                    {{ stat.value }}
                                </p>
                            </div>

                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted"
                            >
                                <component
                                    :is="statIcons[stat.label] ?? ClipboardList"
                                    class="size-4 text-muted-foreground"
                                />
                            </div>
                        </div>

                        <p class="mt-4 text-xs text-muted-foreground">
                            {{ stat.description }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </section>

        <!-- Production Trend -->
        <section v-if="showProductionTrend">
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
                        v-if="productionTrend.length"
                        class="flex h-72 items-end gap-3 overflow-x-auto border-b border-l px-4 pt-8 pb-0"
                    >
                        <div
                            v-for="item in productionTrend"
                            :key="item.date"
                            class="flex min-w-10 flex-1 items-end gap-1"
                        >
                            <div
                                class="flex flex-1 flex-col items-center justify-end gap-2"
                            >
                                <div
                                    :style="{
                                        height: getProductionHeight(
                                            item.productionQuantity,
                                        ),
                                    }"
                                    :title="`Production: ${formatNumber(item.productionQuantity)}`"
                                    class="w-full rounded-t-md bg-primary/80"
                                />

                                <span
                                    class="text-[10px] whitespace-nowrap text-muted-foreground"
                                >
                                    {{ item.label }}
                                </span>
                            </div>

                            <div
                                class="flex flex-1 flex-col items-center justify-end gap-2"
                            >
                                <div
                                    :style="{
                                        height: getRejectionHeight(
                                            item.rejectionQuantity,
                                        ),
                                    }"
                                    :title="`Rejected: ${formatNumber(item.rejectionQuantity)}`"
                                    class="w-full rounded-t-md bg-muted-foreground/40"
                                />

                                <span class="text-[10px] text-transparent">
                                    {{ item.label }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <p
                        v-else
                        class="py-12 text-center text-sm text-muted-foreground"
                    >
                        No production data available for this period.
                    </p>

                    <div
                        class="mt-4 flex flex-wrap items-center justify-center gap-6 text-xs text-muted-foreground"
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

        <!-- Machine Performance + Attendance -->
        <section
            v-if="canViewOperations"
            class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(280px,1fr)]"
        >
            <!-- Machine Performance -->
            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ dashboardPage.machinePerformanceTitle }}
                    </CardTitle>

                    <p class="text-sm text-muted-foreground">
                        {{ dashboardPage.machinePerformanceDescription }}
                    </p>
                </CardHeader>

                <CardContent>
                    <div
                        v-if="machinePerformance.length"
                        class="overflow-x-auto"
                    >
                        <table class="w-full min-w-[520px] text-sm">
                            <thead>
                                <tr
                                    class="border-b text-left text-xs text-muted-foreground"
                                >
                                    <th class="pb-3 font-medium">
                                        {{
                                            dashboardPage.table.machine.machine
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
                                    v-for="machine in machinePerformance"
                                    :key="machine.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-3">
                                        <p class="font-medium">
                                            {{ machine.referenceName }}
                                        </p>

                                        <p
                                            class="max-w-48 truncate text-xs text-muted-foreground"
                                        >
                                            {{ machine.name }}
                                        </p>
                                    </td>

                                    <td class="py-3 text-right tabular-nums">
                                        {{ machine.parts }}
                                    </td>

                                    <td
                                        class="py-3 text-right font-medium tabular-nums"
                                    >
                                        {{ formatNumber(machine.quantity) }}
                                    </td>

                                    <td class="py-3 text-right tabular-nums">
                                        {{ machine.productionTime }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p
                        v-else
                        class="py-10 text-center text-sm text-muted-foreground"
                    >
                        No machine production recorded today.
                    </p>
                </CardContent>
            </Card>

            <!-- Attendance -->
            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ dashboardPage.attendanceTitle }}
                    </CardTitle>
                </CardHeader>

                <CardContent>
                    <div v-if="attendanceItems.length" class="space-y-4">
                        <div
                            v-for="item in attendanceItems"
                            :key="item.label"
                            class="flex items-center justify-between gap-4"
                        >
                            <span class="text-sm text-muted-foreground">
                                {{ item.label }}
                            </span>

                            <span class="text-sm font-semibold tabular-nums">
                                {{ formatNumber(item.value) }}
                            </span>
                        </div>
                    </div>

                    <p
                        v-else
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        No attendance data available today.
                    </p>
                </CardContent>
            </Card>
        </section>

        <!-- Operator Performance -->
        <section v-if="canViewOperations || isOperator">
            <Card>
                <CardHeader>
                    <CardTitle>
                        {{
                            isOperator
                                ? 'My Production Today'
                                : dashboardPage.operatorPerformanceTitle
                        }}
                    </CardTitle>

                    <p class="text-sm text-muted-foreground">
                        {{
                            isOperator
                                ? 'Your recorded production and calculated efficiency.'
                                : dashboardPage.operatorPerformanceDescription
                        }}
                    </p>
                </CardHeader>

                <CardContent>
                    <div
                        v-if="operatorPerformance.length"
                        class="overflow-x-auto"
                    >
                        <table class="w-full min-w-[680px] text-sm">
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
                                    v-for="operator in operatorPerformance"
                                    :key="operator.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-3 font-medium">
                                        {{ operator.name }}
                                    </td>

                                    <td class="py-3 text-right tabular-nums">
                                        {{ formatNumber(operator.quantity) }}
                                    </td>

                                    <td class="py-3 text-right tabular-nums">
                                        {{
                                            formatNumber(
                                                operator.rejectionQuantity,
                                            )
                                        }}
                                    </td>

                                    <td class="py-3 text-right tabular-nums">
                                        {{ operator.productionTime }}
                                    </td>

                                    <td
                                        class="py-3 text-right font-medium tabular-nums"
                                    >
                                        {{
                                            operator.efficiency === null
                                                ? '—'
                                                : `${operator.efficiency}%`
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p
                        v-else
                        class="py-10 text-center text-sm text-muted-foreground"
                    >
                        No operator production recorded today.
                    </p>
                </CardContent>
            </Card>
        </section>

        <!-- Recent Activity -->
        <section>
            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ dashboardPage.recentActivityTitle }}
                    </CardTitle>
                </CardHeader>

                <CardContent>
                    <div v-if="recentActivity.length" class="space-y-5">
                        <div
                            v-for="activity in recentActivity"
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

                    <p
                        v-else
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        {{ dashboardPage.empty.recentActivity }}
                    </p>
                </CardContent>
            </Card>
        </section>
    </div>
</template>
