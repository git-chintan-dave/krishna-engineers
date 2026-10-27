<script lang="ts" setup>
import {computed, ref} from 'vue';
import DashboardStats from './DashboardStats.vue';
import DashboardTrend from './DashboardTrend.vue';
import DashboardPanel from './DashboardPanel.vue';
import type {DashboardPayload, DashboardPeriod, OperatorPerformanceItem,} from '@/types/dashboard';

const props = defineProps<{
    dashboardData?: DashboardPayload;
    loading: boolean;
    refreshing: boolean;
}>();

defineEmits<{
    refresh: [];
}>();

const period = ref<DashboardPeriod>('today');

const production = computed(() => {
    if (!props.dashboardData) return null;
    return props.dashboardData.productionOverview[period.value];
});

const operators = computed<OperatorPerformanceItem[]>(() => {
    if (!props.dashboardData) return [];

    return props.dashboardData.operatorPerformance[period.value]
        .slice()
        .sort((a, b) => (a.rank ?? Number.MAX_SAFE_INTEGER)
            - (b.rank ?? Number.MAX_SAFE_INTEGER));
});

function formatNumber(value: number): string {
    return new Intl.NumberFormat('en-IN').format(value);
}

function formatMoney(value: number): string {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(value);
}

function formatDuration(seconds: number): string {
    const safe = Math.max(0, seconds);
    const hours = Math.floor(safe / 3600);
    const minutes = Math.floor((safe % 3600) / 60);

    return `${hours}h ${String(minutes).padStart(2, '0')}m`;
}
</script>

<template>
    <div class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm text-muted-foreground">Management overview</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">
                    {{ dashboardData?.title ?? 'Administration dashboard' }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ dashboardData?.description ?? 'Loading dashboard data…' }}
                </p>
            </div>

            <button
                type="button"
                class="inline-flex h-9 items-center gap-2 rounded-md border border-border px-3 text-sm font-medium hover:bg-muted disabled:opacity-50"
                :disabled="refreshing"
                @click="$emit('refresh')"
            >
                <span :class="{ 'animate-spin': refreshing }">↻</span>
                {{ refreshing ? 'Refreshing…' : 'Refresh' }}
            </button>
        </header>

        <div v-if="loading && !dashboardData" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div v-for="n in 8" :key="n" class="h-28 animate-pulse rounded-lg border border-border bg-muted/40" />
        </div>

        <template v-else-if="dashboardData">
            <!-- Salary overview -->
            <section v-if="dashboardData.salaryOverview" class="space-y-3">
                <div>
                    <h2 class="text-base font-semibold">Salary overview</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Paid history and current-month estimate.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-lg border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">Last paid month</p>
                        <p class="mt-2 text-lg font-semibold">
                            {{ dashboardData.salaryOverview.lastPaidMonth ?? 'No paid records' }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ formatMoney(dashboardData.salaryOverview.lastPaidAmount) }} paid
                        </p>
                    </article>

                    <article class="rounded-lg border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">This month · estimated</p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums">
                            {{ formatMoney(dashboardData.salaryOverview.currentMonthEstimate) }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Estimate based on recorded attendance
                        </p>
                    </article>

                    <article class="rounded-lg border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">Pending payment</p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums">
                            {{ formatMoney(dashboardData.salaryOverview.pendingAmount) }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ dashboardData.salaryOverview.pendingCount }} pending records
                        </p>
                    </article>

                    <article class="rounded-lg border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">Paid payment</p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums">
                            {{ formatMoney(dashboardData.salaryOverview.paidAmount) }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ dashboardData.salaryOverview.paidCount }} paid records
                        </p>
                    </article>
                </div>

                <DashboardPanel
                    title="Salary payment trend"
                    description="Paid versus pending amounts by month"
                    :empty-message="'No salary trend available.'"
                >
                    <div
                        v-if="dashboardData.salaryOverview.monthlyTrend.length"
                        class="space-y-4 p-4"
                    >
                        <div
                            v-for="item in dashboardData.salaryOverview.monthlyTrend"
                            :key="item.label"
                            class="space-y-2"
                        >
                            <div class="flex justify-between gap-3 text-xs">
                                <span class="text-muted-foreground">{{ item.label }}</span>
                                <span class="text-right tabular-nums">
                                    Paid {{ formatMoney(item.paidAmount) }}
                                    · Pending {{ formatMoney(item.pendingAmount) }}
                                </span>
                            </div>
                            <div class="flex h-3 overflow-hidden rounded-sm bg-muted">
                                <div
                                    class="bg-foreground"
                                    :style="{ width: `${(item.paidAmount / Math.max(1, item.paidAmount + item.pendingAmount)) * 100}%` }"
                                />
                                <div
                                    class="border border-dashed border-foreground/70 bg-background"
                                    :style="{ width: `${(item.pendingAmount / Math.max(1, item.paidAmount + item.pendingAmount)) * 100}%` }"
                                />
                            </div>
                        </div>
                    </div>
                </DashboardPanel>
            </section>

            <!-- Factory production -->
            <section class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold">Production overview</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Operator output combines good and rejected pieces.
                        </p>
                    </div>

                    <div class="inline-flex rounded-md border border-border p-1">
                        <button
                            v-for="option in [
                                { label: 'Today', value: 'today' },
                                { label: 'This week', value: 'week' },
                                { label: 'This month', value: 'month' },
                            ] as const"
                            :key="option.value"
                            type="button"
                            class="rounded px-3 py-1.5 text-xs font-medium"
                            :class="period === option.value ? 'bg-foreground text-background' : 'text-muted-foreground hover:bg-muted'"
                            @click="period = option.value"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <div v-if="production" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-lg border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">Total pieces</p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums">
                            {{ formatNumber(production.totalQuantity) }}
                        </p>
                    </article>
                    <article class="rounded-lg border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">Good pieces</p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums">
                            {{ formatNumber(production.goodQuantity) }}
                        </p>
                    </article>
                    <article class="rounded-lg border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">Rejected pieces</p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums">
                            {{ formatNumber(production.rejectedQuantity) }}
                        </p>
                    </article>
                    <article class="rounded-lg border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">Production time</p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums">
                            {{ formatDuration(production.productionSeconds) }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Actual cycle time × logged quantity
                        </p>
                    </article>
                </div>

                <DashboardTrend
                    title="Daily production trend"
                    description="Good pieces versus rejected pieces"
                    :points="dashboardData.productionOverview.dailyTrend"
                />
            </section>

            <!-- Attendance -->
            <section class="space-y-3">
                <h2 class="text-base font-semibold">Attendance overview</h2>
                <DashboardStats
                    :stats="[
                        { label: 'Present', value: dashboardData.attendanceOverview.present, description: 'Employees with attendance records' },
                        { label: 'Not recorded', value: dashboardData.attendanceOverview.notRecorded, description: 'Not necessarily confirmed absent' },
                        { label: 'Open shifts', value: dashboardData.attendanceOverview.openShifts, description: 'Attendance without a checkout' },
                        { label: 'Total employees', value: dashboardData.attendanceOverview.totalEmployees, description: 'Employees included in the overview' },
                    ]"
                />
            </section>

            <!-- Machines and parts -->
            <section class="grid min-h-0 gap-4 xl:grid-cols-2">
                <DashboardPanel title="Machine production" description="Output logged against each machine">
                    <div v-if="dashboardData.machinePerformance.length" class="divide-y divide-border">
                        <article
                            v-for="machine in dashboardData.machinePerformance"
                            :key="machine.machineId"
                            class="p-4"
                        >
                            <div class="flex flex-wrap justify-between gap-2">
                                <p class="text-sm font-medium">{{ machine.machineName }}</p>
                                <p class="text-sm font-semibold tabular-nums">
                                    {{ formatNumber(machine.totalQuantity) }} pieces
                                </p>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                <span>Good: {{ formatNumber(machine.goodQuantity) }}</span>
                                <span>Rejected: {{ formatNumber(machine.rejectedQuantity) }}</span>
                                <span>Time: {{ formatDuration(machine.productionSeconds) }}</span>
                            </div>
                            <div class="mt-3 flex h-2 overflow-hidden rounded-sm bg-muted">
                                <div
                                    class="bg-foreground"
                                    :style="{ width: `${machine.totalQuantity ? machine.goodQuantity / machine.totalQuantity * 100 : 0}%` }"
                                />
                                <div
                                    class="border border-dashed border-foreground/70 bg-background"
                                    :style="{ width: `${machine.totalQuantity ? machine.rejectedQuantity / machine.totalQuantity * 100 : 0}%` }"
                                />
                            </div>
                        </article>
                    </div>
                    <p v-else class="p-4 text-sm text-muted-foreground">No machine production recorded.</p>
                </DashboardPanel>

                <DashboardPanel title="Top parts produced" description="Operator output by part">
                    <div v-if="dashboardData.partPerformance.length" class="divide-y divide-border">
                        <article
                            v-for="part in dashboardData.partPerformance"
                            :key="part.partId"
                            class="flex flex-wrap items-start justify-between gap-3 p-4"
                        >
                            <div class="min-w-0">
                                <p class="break-words text-sm font-medium">{{ part.partName }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Good {{ formatNumber(part.goodQuantity) }}
                                    · Rejected {{ formatNumber(part.rejectedQuantity) }}
                                </p>
                            </div>
                            <p class="shrink-0 text-sm font-semibold tabular-nums">
                                {{ formatNumber(part.totalQuantity) }} pcs
                            </p>
                        </article>
                    </div>
                    <p v-else class="p-4 text-sm text-muted-foreground">No part production recorded.</p>
                </DashboardPanel>
            </section>

            <!-- Operator ranking -->
            <DashboardPanel title="Operator performance" description="Ranked by efficiency for the selected period">
                <template #actions>
                    <span class="text-xs text-muted-foreground">
                        {{ period === 'today' ? 'Today' : period === 'week' ? 'This week' : 'This month' }}
                    </span>
                </template>

                <div v-if="operators.length" class="divide-y divide-border">
                    <article
                        v-for="operator in operators"
                        :key="operator.userId"
                        class="flex flex-wrap items-center justify-between gap-3 p-4"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-md border border-border text-sm font-semibold tabular-nums">
                                {{ operator.rank ?? '—' }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ operator.name }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ operator.employeeCode }}
                                    · {{ formatNumber(operator.totalQuantity) }} pieces
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold tabular-nums">
                                {{ operator.efficiency === null ? '—' : `${operator.efficiency.toFixed(1)}%` }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">Efficiency</p>
                        </div>
                    </article>
                </div>
                <p v-else class="p-4 text-sm text-muted-foreground">
                    No operator performance data available for this period.
                </p>
            </DashboardPanel>

            <!-- Inventory warnings -->
            <DashboardPanel
                title="Low-stock alerts"
                description="Tools and raw materials below their configured thresholds"
            >
                <div v-if="dashboardData.inventoryAlerts.length" class="divide-y divide-border">
                    <article
                        v-for="item in dashboardData.inventoryAlerts"
                        :key="`${item.type}-${item.id}`"
                        class="flex flex-wrap items-center justify-between gap-3 p-4"
                    >
                        <div>
                            <p class="text-sm font-medium">{{ item.name }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ item.type === 'tool' ? 'Tool' : 'Raw material' }}
                                · Threshold {{ item.threshold }}
                            </p>
                        </div>
                        <p class="text-sm font-semibold tabular-nums">
                            Stock: {{ item.currentQuantity }}
                        </p>
                    </article>
                </div>
                <p v-else class="p-4 text-sm text-muted-foreground">
                    No low-stock alerts. This assumes thresholds are configured.
                </p>
            </DashboardPanel>

            <!-- Recent activity -->
            <section class="grid min-h-0 gap-4 xl:grid-cols-2">
                <DashboardPanel
                    v-for="section in dashboardData.sections"
                    :key="section.title"
                    :title="section.title"
                    :empty-message="section.emptyMessage"
                >
                    <div v-if="section.items.length" class="divide-y divide-border">
                        <article
                            v-for="(item, index) in section.items"
                            :key="`${item.title}-${index}`"
                            class="p-4"
                        >
                            <p class="break-words text-sm font-medium">{{ item.title }}</p>
                            <p v-if="item.subtitle" class="mt-1 break-words text-xs text-muted-foreground">
                                {{ item.subtitle }}
                            </p>
                            <p v-if="item.meta" class="mt-1 break-words text-xs text-muted-foreground">
                                {{ item.meta }}
                            </p>
                        </article>
                    </div>
                </DashboardPanel>
            </section>
        </template>

        <section v-else class="rounded-lg border border-border p-6">
            <h2 class="font-semibold">Dashboard data unavailable</h2>
            <p class="mt-2 text-sm text-muted-foreground">
                Refresh the dashboard to try again.
            </p>
        </section>
    </div>
</template>
