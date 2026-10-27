<script lang="ts" setup>
import {computed, ref} from 'vue';
import {RefreshCw} from '@lucide/vue';

import DashboardPanel from './DashboardPanel.vue';
import DashboardStats from './DashboardStats.vue';
import DashboardTrend from './DashboardTrend.vue';

import type {
    AttendanceOverview,
    DashboardPeriod,
    inventoryAlerts,
    ProductionOverview,
    SalaryOverview,
} from '@/types/dashboard';

const props = defineProps<{
    salaryOverview?: SalaryOverview;
    attendanceOverview?: AttendanceOverview;
    inventoryAlerts?: inventoryAlerts[];
    productionOverview?: ProductionOverview;
    loading: boolean;
    refreshing: boolean;
}>();

const emit = defineEmits<{
    refresh: [];
}>();

const period = ref<DashboardPeriod>('today');

const periodOptions = [
    { label: 'Today', value: 'today' },
    { label: 'This week', value: 'week' },
    { label: 'This month', value: 'month' },
] as const;

const inventoryTabs = [
    { value: 'all', label: 'All' },
    { value: 'raw_material', label: 'Raw materials' },
    { value: 'tool', label: 'Tools' },
] as const;

type InventoryTab = (typeof inventoryTabs)[number]['value'];

const inventoryTab = ref<InventoryTab>('all');

const production = computed(
    () =>
        props.productionOverview?.[period.value] ?? {
            totalQuantity: 0,
            goodQuantity: 0,
            rejectedQuantity: 0,
            productionSeconds: 0,
        },
);

const dailyTrend = computed(
    () => props.productionOverview?.dailyTrend ?? [],
);

const inventoryAlertsList = computed(
    () => props.inventoryAlerts ?? [],
);

const filteredInventoryAlerts = computed(() => {
    if (inventoryTab.value === 'all') {
        return inventoryAlertsList.value;
    }

    return inventoryAlertsList.value.filter(
        (item) => item.type === inventoryTab.value,
    );
});

const inventoryTabCounts = computed<Record<InventoryTab, number>>(() => ({
    all: inventoryAlertsList.value.length,
    raw_material: inventoryAlertsList.value.filter(
        (item) => item.type === 'raw_material',
    ).length,
    tool: inventoryAlertsList.value.filter(
        (item) => item.type === 'tool',
    ).length,
}));

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

function percentage(value: number, total: number): number {
    return total > 0
        ? Math.min(100, Math.max(0, (value / total) * 100))
        : 0;
}
</script>

<template>
    <div class="space-y-5">
        <!-- Header -->
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm text-muted-foreground">
                    Management overview
                </p>

                <h1 class="mt-1 text-2xl font-semibold tracking-tight">
                    Administration dashboard
                </h1>

                <p class="mt-1 text-sm text-muted-foreground">
                    Monitor factory production, attendance, employee performance,
                    and operational costs.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex h-9 items-center gap-2 rounded-md border border-border px-3 text-sm font-medium hover:bg-muted disabled:opacity-50"
                :disabled="refreshing"
                @click="emit('refresh')"
            >
                <RefreshCw
                    class="size-4"
                    :class="{ 'animate-spin': refreshing }"
                    aria-hidden="true"
                />

                {{ refreshing ? 'Refreshing…' : 'Refresh' }}
            </button>
        </header>

        <!-- Initial loading -->
        <div
            v-if="loading"
            class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
            aria-label="Loading dashboard"
        >
            <div
                v-for="n in 8"
                :key="n"
                class="h-28 animate-pulse rounded-lg border border-border bg-muted/40"
            />
        </div>

        <template v-else>
            <!-- Salary overview -->
            <section class="space-y-3">
                <div>
                    <h2 class="text-base font-semibold">
                        Salary overview
                    </h2>

                    <p class="mt-1 text-sm text-muted-foreground">
                        Paid history and current-month salary estimate.
                    </p>
                </div>

                <div
                    v-if="salaryOverview"
                    class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <article class="rounded-lg border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">
                            Last paid month
                        </p>

                        <p class="mt-2 text-lg font-semibold">
                            {{ salaryOverview.lastPaidMonth || 'No payments yet' }}
                        </p>

                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ formatMoney(salaryOverview.lastPaidAmount) }} paid
                        </p>
                    </article>

                    <article class="rounded-lg border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">
                            This month · estimated
                        </p>

                        <p class="mt-2 text-2xl font-semibold tabular-nums">
                            {{ formatMoney(salaryOverview.currentMonthEstimate) }}
                        </p>

                        <p class="mt-1 text-xs text-muted-foreground">
                            Based on employee salaries and monthly working days
                        </p>
                    </article>

                    <article class="rounded-lg border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">
                            Pending payment
                        </p>

                        <p class="mt-2 text-2xl font-semibold tabular-nums">
                            {{ formatMoney(salaryOverview.pendingAmount) }}
                        </p>

                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ salaryOverview.pendingCount }}
                            {{ salaryOverview.pendingCount === 1 ? 'record' : 'records' }}
                        </p>
                    </article>

                    <article class="rounded-lg border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">
                            Paid payment
                        </p>

                        <p class="mt-2 text-2xl font-semibold tabular-nums">
                            {{ formatMoney(salaryOverview.paidAmount) }}
                        </p>

                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ salaryOverview.paidCount }}
                            {{ salaryOverview.paidCount === 1 ? 'record' : 'records' }}
                        </p>
                    </article>
                </div>

                <div
                    v-else
                    class="rounded-lg border border-border bg-card p-4"
                >
                    <p class="text-sm text-muted-foreground">
                        Salary overview is unavailable.
                    </p>
                </div>

                <!-- Salary payment trend -->
                <DashboardPanel
                    title="Salary payment trend"
                    description="Paid versus pending amounts by month"
                    empty-message="No salary trend available."
                >
                    <div
                        v-if="salaryOverview?.monthlyTrend?.length"
                        class="space-y-4 p-4"
                    >
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-muted-foreground">
                            <div class="flex items-center gap-2">
                                <span class="size-2.5 rounded-sm bg-foreground" />
                                <span>Paid</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="size-2.5 rounded-sm border border-dashed border-foreground/70 bg-background" />
                                <span>Pending</span>
                            </div>
                        </div>

                        <div
                            v-for="item in salaryOverview.monthlyTrend"
                            :key="item.label"
                            class="space-y-2"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                <span class="font-medium">
                                    {{ item.label }}
                                </span>

                                <span class="text-right text-muted-foreground tabular-nums">
                                    Paid {{ formatMoney(item.paidAmount) }}
                                    <span class="mx-1">·</span>
                                    Pending {{ formatMoney(item.pendingAmount) }}
                                </span>
                            </div>

                            <div
                                class="flex h-3 overflow-hidden rounded-sm bg-muted"
                                role="img"
                                :aria-label="`${item.label}: paid ${formatMoney(item.paidAmount)}, pending ${formatMoney(item.pendingAmount)}`"
                            >
                                <div
                                    class="bg-foreground transition-[width]"
                                    :style="{
                                        width: `${percentage(item.paidAmount, item.paidAmount + item.pendingAmount)}%`,
                                    }"
                                />

                                <div
                                    class="border border-dashed border-foreground/70 bg-background transition-[width]"
                                    :style="{
                                        width: `${percentage(item.pendingAmount, item.paidAmount + item.pendingAmount)}%`,
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                </DashboardPanel>
            </section>

            <!-- Production overview -->
            <section class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold">
                            Production overview
                        </h2>

                        <p class="mt-1 text-sm text-muted-foreground">
                            Operator output combines good and rejected pieces.
                        </p>
                    </div>

                    <div
                        class="inline-flex rounded-md border border-border p-1"
                        aria-label="Production period"
                    >
                        <button
                            v-for="option in periodOptions"
                            :key="option.value"
                            type="button"
                            class="rounded px-3 py-1.5 text-xs font-medium transition-colors"
                            :class="
                                period === option.value
                                    ? 'bg-foreground text-background'
                                    : 'text-muted-foreground hover:bg-muted'
                            "
                            :aria-pressed="period === option.value"
                            @click="period = option.value"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-lg border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">
                            Total pieces
                        </p>

                        <p class="mt-2 text-2xl font-semibold tabular-nums">
                            {{ formatNumber(production.totalQuantity) }}
                        </p>
                    </article>

                    <article class="rounded-lg border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">
                            Good pieces
                        </p>

                        <p class="mt-2 text-2xl font-semibold tabular-nums">
                            {{ formatNumber(production.goodQuantity) }}
                        </p>
                    </article>

                    <article class="rounded-lg border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">
                            Rejected pieces
                        </p>

                        <p class="mt-2 text-2xl font-semibold tabular-nums">
                            {{ formatNumber(production.rejectedQuantity) }}
                        </p>
                    </article>

                    <article class="rounded-lg border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">
                            Production time
                        </p>

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
                    :points="dailyTrend"
                />
            </section>

            <!-- Attendance overview -->
            <section class="space-y-3">
                <div>
                    <h2 class="text-base font-semibold">
                        Attendance overview
                    </h2>

                    <p class="mt-1 text-sm text-muted-foreground">
                        Today's employee attendance status.
                    </p>
                </div>

                <DashboardStats
                    :stats="[
                        {
                            label: 'Present',
                            value: attendanceOverview?.present ?? 0,
                            description: 'Employees with attendance records',
                        },
                        {
                            label: 'Not recorded',
                            value: attendanceOverview?.notRecorded ?? 0,
                            description: 'Not necessarily confirmed absent',
                        },
                        {
                            label: 'Open shifts',
                            value: attendanceOverview?.openShifts ?? 0,
                            description: 'Attendance without a checkout',
                        },
                        {
                            label: 'Total employees',
                            value: attendanceOverview?.totalEmployees ?? 0,
                            description: 'Active employees included in the overview',
                        },
                    ]"
                />
            </section>

            <!-- Inventory alerts -->
            <DashboardPanel
                title="Low-stock alerts"
                description="Tools and raw materials at or below their configured thresholds"
                empty-message="No low-stock alerts."
            >
                <div>
                    <!-- Inventory category tabs -->
                    <div class="border-b px-4 pt-3">
                        <div
                            class="flex gap-5 overflow-x-auto"
                            role="tablist"
                            aria-label="Inventory alert categories"
                        >
                            <button
                                v-for="tab in inventoryTabs"
                                :key="tab.value"
                                type="button"
                                role="tab"
                                :aria-selected="inventoryTab === tab.value"
                                class="shrink-0 border-b-2 px-1 pb-3 text-sm font-medium transition-colors"
                                :class="
                                    inventoryTab === tab.value
                                        ? 'border-primary text-foreground'
                                        : 'border-transparent text-muted-foreground hover:text-foreground'
                                "
                                @click="inventoryTab = tab.value"
                            >
                                {{ tab.label }}

                                <span class="ml-1 text-xs tabular-nums">
                                    {{ inventoryTabCounts[tab.value] }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Scrollable inventory list -->
                    <div class="max-h-80 overflow-y-auto overscroll-contain">
                        <div
                            v-if="filteredInventoryAlerts.length"
                            class="divide-y divide-border"
                        >
                            <article
                                v-for="item in filteredInventoryAlerts"
                                :key="`${item.type}-${item.id}`"
                                class="flex flex-wrap items-center justify-between gap-3 p-4"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">
                                        {{ item.name }}
                                    </p>

                                    <p class="mt-1 text-xs text-muted-foreground">
                                        {{ item.type === 'tool' ? 'Tool' : 'Raw material' }}
                                        · Threshold {{ item.threshold }}
                                    </p>
                                </div>

                                <p class="shrink-0 text-sm font-semibold tabular-nums">
                                    Stock: {{ formatNumber(item.currentQuantity) }}
                                </p>
                            </article>
                        </div>

                        <p
                            v-else
                            class="px-4 py-8 text-center text-sm text-muted-foreground"
                        >
                            {{
                                inventoryTab === 'all'
                                    ? 'No low-stock alerts.'
                                    : `No low-stock alerts for ${inventoryTab === 'tool' ? 'tools' : 'raw materials'}.`
                            }}
                        </p>
                    </div>
                </div>
            </DashboardPanel>
        </template>
    </div>
</template>
