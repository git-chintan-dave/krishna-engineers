<script lang="ts" setup>
import {computed, ref, watch} from 'vue';
import {CheckCircle2, Clock3, Users, Wallet,} from '@lucide/vue';

import ListingStats from '@/components/common/ListingStats.vue';
import SectionHeader from '@/components/common/SectionHeader.vue';
import SegmentedTabs from '@/components/common/SegmentedTabs.vue';

import type {SalaryDashboard, SalaryPeriod,} from '@/types/dashboard';

type Period = 'current' | 'previous';

const props = defineProps<{
    salaryDashboard?: SalaryDashboard | null;
}>();

const selectedPeriod = ref<Period>('current');

const isLoading = computed(
    () => props.salaryDashboard === undefined,
);

const currentPeriod = computed(
    () => props.salaryDashboard?.currentPeriod ?? null,
);

const previousPeriod = computed(
    () => props.salaryDashboard?.previousPeriod ?? null,
);

const hasSalaryData = computed(
    () => currentPeriod.value !== null,
);

const periodTabs = computed(() => [
    {
        value: 'current' as const,
        label: 'Current period',
    },
    ...(previousPeriod.value
        ? [
            {
                value: 'previous' as const,
                label: 'Previous period',
            },
        ]
        : []),
]);

const activePeriod = computed<SalaryPeriod | null>(() => {
    if (
        selectedPeriod.value === 'previous' &&
        previousPeriod.value
    ) {
        return previousPeriod.value;
    }

    return currentPeriod.value;
});

watch(previousPeriod, (period) => {
    if (!period && selectedPeriod.value === 'previous') {
        selectedPeriod.value = 'current';
    }
});

const paymentPercentage = computed(
    () => activePeriod.value?.paymentPercentage ?? 0,
);

const summaryStats = computed(() => {
    const period = activePeriod.value;

    if (!period) {
        return [];
    }

    return [
        {
            label: 'Total payable',
            value: period.totalPayableFormatted,
            icon: Wallet,
            description: period.label,
            variant: 'default' as const,
        },
        {
            label: 'Paid amount',
            value: period.paidAmountFormatted,
            icon: CheckCircle2,
            description: `${period.paidEmployees} employees paid`,
            variant: 'success' as const,
        },
        {
            label: 'Pending amount',
            value: period.pendingAmountFormatted,
            icon: Clock3,
            description: `${period.pendingEmployees} employees pending`,
            variant: 'warning' as const,
        },
        {
            label: 'Employees',
            value: period.employeeCount,
            icon: Users,
            description: 'Salary records generated',
            variant: 'info' as const,
        },
    ];
});

const breakdownItems = computed(() => {
    const breakdown = activePeriod.value?.breakdown;

    if (!breakdown) {
        return [];
    }

    return [
        {
            label: 'Base salary',
            amount: breakdown.baseSalaryFormatted,
            type: 'normal',
        },
        {
            label: 'Incentives',
            amount: breakdown.incentiveFormatted,
            type: 'positive',
        },
        {
            label: 'Adjustments',
            amount: breakdown.adjustmentFormatted,
            type: 'normal',
        },
        {
            label: 'Deductions',
            amount: breakdown.deductionFormatted,
            type: 'negative',
        },
        {
            label: 'UPAD',
            amount: breakdown.upadFormatted,
            type: 'negative',
        },
        {
            label: 'Loan recovery',
            amount: breakdown.paidLoanFormatted,
            type: 'negative',
        },
    ];
});

const comparisonPeriods = computed(() => {
    return [
        ...(previousPeriod.value ? [previousPeriod.value] : []),
        ...(currentPeriod.value ? [currentPeriod.value] : []),
    ];
});

const comparisonMax = computed(() =>
    Math.max(
        ...comparisonPeriods.value.map(
            period => period.totalPayable,
        ),
        1,
    ),
);

const getBarHeight = (amount: number) =>
    `${(amount / comparisonMax.value) * 100}%`;
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <SectionHeader
            title="Salary overview"
            description="Salary summary and payment status"
            :icon="Wallet"
        >
            <template #actions>
                <SegmentedTabs
                    v-if="previousPeriod"
                    v-model="selectedPeriod"
                    :items="periodTabs"
                    aria-label="Salary period"
                    variant="primary"
                    size="sm"
                />
            </template>
        </SectionHeader>

        <!-- Loading state -->
        <div
            v-if="isLoading"
            class="space-y-6"
        >
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div
                    v-for="item in 4"
                    :key="item"
                    class="h-28 animate-pulse rounded-xl border border-border bg-muted/40"
                />
            </div>

            <div class="h-44 animate-pulse rounded-xl border border-border bg-muted/40" />

            <div class="grid gap-4 xl:grid-cols-2">
                <div class="h-80 animate-pulse rounded-xl border border-border bg-muted/40" />
                <div class="h-80 animate-pulse rounded-xl border border-border bg-muted/40" />
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-else-if="!hasSalaryData"
            class="flex min-h-72 flex-col items-center justify-center rounded-xl border border-border bg-card px-4 py-12 text-center"
        >
            <div class="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <Wallet class="size-6" />
            </div>

            <h3 class="mt-4 text-sm font-semibold">
                No salary records found
            </h3>

            <p class="mt-1 max-w-sm text-xs text-muted-foreground">
                Salary information will appear here once salary records have been generated.
            </p>
        </div>

        <!-- Loaded state -->
        <template v-else-if="activePeriod">
            <!-- Summary statistics -->
            <ListingStats
                :items="summaryStats"
                :columns="4"
            />

            <!-- Payment progress -->
            <div class="rounded-xl border border-border bg-card p-5">
                <SectionHeader
                    title="Payment progress"
                    :description="`Salary payment completion for ${activePeriod.label}`"
                >
                    <template #actions>
                        <p class="text-xl font-bold tabular-nums text-green-600">
                            {{ activePeriod.paymentPercentageFormatted }}
                        </p>
                    </template>
                </SectionHeader>

                <div class="mt-5 h-3 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full bg-green-500 transition-all duration-500"
                        :style="{ width: `${paymentPercentage}%` }"
                    />
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                    <span>
                        Paid:
                        <strong class="font-medium text-foreground">
                            {{ activePeriod.paidAmountFormatted }}
                        </strong>
                    </span>

                    <span>
                        Pending:
                        <strong class="font-medium text-foreground">
                            {{ activePeriod.pendingAmountFormatted }}
                        </strong>
                    </span>
                </div>
            </div>

            <!-- Breakdown and employee status -->
            <div class="grid gap-4 xl:grid-cols-2">
                <!-- Salary breakdown -->
                <div class="rounded-xl border border-border bg-card">
                    <div class="border-b border-border p-5">
                        <SectionHeader
                            title="Salary breakdown"
                            :description="`Salary components for ${activePeriod.label}`"
                        />
                    </div>

                    <div class="divide-y divide-border px-5">
                        <div
                            v-for="item in breakdownItems"
                            :key="item.label"
                            class="flex items-center justify-between gap-3 py-3.5"
                        >
                            <span class="text-sm text-muted-foreground">
                                {{ item.label }}
                            </span>

                            <span
                                class="text-sm font-medium tabular-nums"
                                :class="{
                                    'text-green-600': item.type === 'positive',
                                    'text-red-600': item.type === 'negative',
                                }"
                            >
                                {{ item.amount }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-border bg-muted/30 px-5 py-4">
                        <span class="text-sm font-semibold">
                            Final payable
                        </span>

                        <span class="text-lg font-bold tabular-nums">
                            {{ activePeriod.totalPayableFormatted }}
                        </span>
                    </div>
                </div>

                <!-- Employee payment status -->
                <div class="rounded-xl border border-border bg-card">
                    <div class="flex items-center justify-between gap-3 border-b border-border p-5">
                        <SectionHeader
                            title="Employee payment status"
                            :description="`Payment details for ${activePeriod.label}`"
                        />

                        <span class="shrink-0 rounded-md bg-muted px-2 py-1 text-xs font-medium">
                            {{ activePeriod.employees.length }} records
                        </span>
                    </div>

                    <div
                        v-if="activePeriod.employees.length"
                        class="max-h-[360px] divide-y divide-border overflow-y-auto"
                    >
                        <div
                            v-for="employee in activePeriod.employees"
                            :key="employee.id"
                            class="flex items-center justify-between gap-3 px-5 py-3.5"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">
                                    {{ employee.name }}
                                </p>

                                <p
                                    v-if="employee.status === 'paid' && employee.paidAt"
                                    class="mt-0.5 text-xs text-muted-foreground"
                                >
                                    Paid on {{ employee.paidAt }}
                                </p>

                                <p
                                    v-else
                                    class="mt-0.5 text-xs text-muted-foreground"
                                >
                                    Payment pending
                                </p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="text-sm font-semibold tabular-nums">
                                    {{ employee.amountFormatted }}
                                </p>

                                <span
                                    class="mt-1 inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="employee.status === 'paid'
                                        ? 'bg-green-500/10 text-green-600'
                                        : 'bg-amber-500/10 text-amber-600'"
                                >
                                    {{ employee.status === 'paid' ? 'Paid' : 'Pending' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex min-h-48 items-center justify-center p-6 text-center"
                    >
                        <div>
                            <p class="text-sm font-medium">
                                No employee records found
                            </p>

                            <p class="mt-1 text-xs text-muted-foreground">
                                There are no employee salary records for this period.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly comparison -->
            <div class="rounded-xl border border-border bg-card">
                <div class="border-b border-border p-5">
                    <SectionHeader
                        title="Salary period comparison"
                        description="Compare payable and payment status across salary periods"
                    />
                </div>

                <div class="space-y-5 p-5">
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                            <span class="size-2.5 rounded-sm bg-green-500" />
                            Paid
                        </div>

                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                            <span class="size-2.5 rounded-sm bg-amber-500" />
                            Pending
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div
                            v-for="period in comparisonPeriods"
                            :key="period.startDate + period.endDate"
                            class="min-w-0"
                        >
                            <div class="mb-3 text-center">
                                <p class="text-sm font-semibold">
                                    {{ period.label }}
                                </p>

                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ period.totalPayableFormatted }}
                                </p>
                            </div>

                            <div class="flex h-48 items-end justify-center border-b border-dashed border-border">
                                <div
                                    class="flex w-20 max-w-full flex-col justify-end overflow-hidden rounded-t-md bg-muted"
                                    :style="{ height: getBarHeight(period.totalPayable) }"
                                    :title="`Total payable: ${period.totalPayableFormatted}`"
                                >
                                    <div
                                        class="flex min-h-0 flex-col-reverse overflow-hidden"
                                        :style="{ height: `${period.paymentPercentage}%` }"
                                    >
                                        <div
                                            class="min-h-0 flex-1 bg-green-500"
                                            :title="`Paid: ${period.paidAmountFormatted}`"
                                        />
                                    </div>

                                    <div
                                        class="min-h-0 flex-1 bg-amber-500"
                                        :title="`Pending: ${period.pendingAmountFormatted}`"
                                    />
                                </div>
                            </div>

                            <div class="mt-3 space-y-1 text-center">
                                <p class="text-xs text-green-600">
                                    Paid: {{ period.paidAmountFormatted }}
                                </p>

                                <p class="text-xs text-amber-600">
                                    Pending: {{ period.pendingAmountFormatted }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <p class="text-center text-xs text-muted-foreground">
                        Comparison is based on salary periods, not payment dates.
                    </p>
                </div>
            </div>
        </template>
    </div>
</template>
