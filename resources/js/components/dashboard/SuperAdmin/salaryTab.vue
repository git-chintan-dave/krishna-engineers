<script lang="ts" setup>
import {computed, ref} from 'vue';
import {CheckCircle2, Clock3, Users, Wallet,} from '@lucide/vue';

import ListingStats from '@/components/common/ListingStats.vue';
import SectionHeader from '@/components/common/SectionHeader.vue';
import SegmentedTabs from '@/components/common/SegmentedTabs.vue';

type Period = 'current' | 'previous';
type PaymentStatus = 'paid' | 'pending';

interface EmployeePayment {
    id: number;
    name: string;
    amount: number;
    status: PaymentStatus;
    paidAt?: string;
}

interface SalaryPeriod {
    label: string;
    totalPayable: number;
    paidAmount: number;
    pendingAmount: number;
    employeeCount: number;
    paidEmployees: number;
    pendingEmployees: number;
    breakdown: {
        baseSalary: number;
        incentive: number;
        deduction: number;
        upad: number;
        paidLoan: number;
        adjustment: number;
    };
    employees: EmployeePayment[];
}

const selectedPeriod = ref<Period>('current');

const periodTabs = [
    { value: 'current', label: 'Current month' },
    { value: 'previous', label: 'Previous month' },
] as const;

const salaryPeriods: Record<Period, SalaryPeriod> = {
    current: {
        label: 'November 2026',
        totalPayable: 245000,
        paidAmount: 180000,
        pendingAmount: 65000,
        employeeCount: 18,
        paidEmployees: 13,
        pendingEmployees: 5,
        breakdown: {
            baseSalary: 220000,
            incentive: 15000,
            deduction: 5000,
            upad: 8000,
            paidLoan: 2000,
            adjustment: 5000,
        },
        employees: [
            { id: 1, name: 'Sanjay', amount: 15000, status: 'paid', paidAt: '07 Nov 2026' },
            { id: 2, name: 'Chetan', amount: 18000, status: 'paid', paidAt: '07 Nov 2026' },
            { id: 3, name: 'Mahesh', amount: 16000, status: 'paid', paidAt: '07 Nov 2026' },
            { id: 4, name: 'Ramesh', amount: 17500, status: 'pending' },
            { id: 5, name: 'Paresh', amount: 14500, status: 'paid', paidAt: '07 Nov 2026' },
            { id: 6, name: 'Nilesh', amount: 19000, status: 'pending' },
            { id: 7, name: 'Vijay', amount: 16500, status: 'paid', paidAt: '07 Nov 2026' },
            { id: 8, name: 'Kiran', amount: 15500, status: 'pending' },
        ],
    },

    previous: {
        label: 'October 2026',
        totalPayable: 230000,
        paidAmount: 230000,
        pendingAmount: 0,
        employeeCount: 17,
        paidEmployees: 17,
        pendingEmployees: 0,
        breakdown: {
            baseSalary: 210000,
            incentive: 12000,
            deduction: 4000,
            upad: 6000,
            paidLoan: 3000,
            adjustment: 5000,
        },
        employees: [
            { id: 11, name: 'Sanjay', amount: 15000, status: 'paid', paidAt: '07 Nov 2026' },
            { id: 12, name: 'Chetan', amount: 18000, status: 'paid', paidAt: '07 Nov 2026' },
            { id: 13, name: 'Mahesh', amount: 16000, status: 'paid', paidAt: '07 Nov 2026' },
            { id: 14, name: 'Ramesh', amount: 17500, status: 'paid', paidAt: '07 Nov 2026' },
            { id: 15, name: 'Paresh', amount: 14500, status: 'paid', paidAt: '07 Nov 2026' },
        ],
    },
};

const activePeriod = computed(
    () => salaryPeriods[selectedPeriod.value],
);

const paymentPercentage = computed(() => {
    if (!activePeriod.value.totalPayable) return 0;

    return Math.min(
        100,
        (activePeriod.value.paidAmount / activePeriod.value.totalPayable) * 100,
    );
});

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(amount);

const formatPercentage = (value: number) => `${value.toFixed(1)}%`;

const summaryStats = computed(() => [
    {
        label: 'Total payable',
        value: formatCurrency(activePeriod.value.totalPayable),
        icon: Wallet,
        description: activePeriod.value.label,
        variant: 'default' as const,
    },
    {
        label: 'Paid amount',
        value: formatCurrency(activePeriod.value.paidAmount),
        icon: CheckCircle2,
        description: `${activePeriod.value.paidEmployees} employees paid`,
        variant: 'success' as const,
    },
    {
        label: 'Pending amount',
        value: formatCurrency(activePeriod.value.pendingAmount),
        icon: Clock3,
        description: `${activePeriod.value.pendingEmployees} employees pending`,
        variant: 'warning' as const,
    },
    {
        label: 'Employees',
        value: activePeriod.value.employeeCount,
        icon: Users,
        description: 'Salary records generated',
        variant: 'info' as const,
    },
]);

const breakdownItems = computed(() => [
    {
        label: 'Base salary',
        amount: activePeriod.value.breakdown.baseSalary,
        type: 'normal',
    },
    {
        label: 'Incentives',
        amount: activePeriod.value.breakdown.incentive,
        type: 'positive',
    },
    {
        label: 'Adjustments',
        amount: activePeriod.value.breakdown.adjustment,
        type: 'positive',
    },
    {
        label: 'Deductions',
        amount: activePeriod.value.breakdown.deduction,
        type: 'negative',
    },
    {
        label: 'UPAD',
        amount: activePeriod.value.breakdown.upad,
        type: 'negative',
    },
    {
        label: 'Loan recovery',
        amount: activePeriod.value.breakdown.paidLoan,
        type: 'negative',
    },
]);

const comparisonMax = computed(() =>
    Math.max(
        salaryPeriods.current.totalPayable,
        salaryPeriods.previous.totalPayable,
        1,
    ),
);

const comparisonPeriods = computed(() => [
    salaryPeriods.previous,
    salaryPeriods.current,
]);

const getBarHeight = (amount: number) =>
    `${(amount / comparisonMax.value) * 100}%`;

const getPaymentRatio = (period: SalaryPeriod) => {
    if (!period.totalPayable) return 0;

    return Math.min(
        100,
        (period.paidAmount / period.totalPayable) * 100,
    );
};
</script>

<template>
    <div class="space-y-6">
        <!-- Salary overview -->
        <SectionHeader
            title="Salary overview"
            description="Salary summary and payment status"
            :icon="Wallet"
        >
            <template #actions>
                <SegmentedTabs
                    v-model="selectedPeriod"
                    :items="periodTabs"
                    aria-label="Salary period"
                    variant="primary"
                    size="sm"
                />
            </template>
        </SectionHeader>

        <!-- Summary statistics -->
        <ListingStats
            :items="summaryStats"
            :columns="4"
        />

        <!-- Payment progress -->
        <div class="rounded-xl border bg-card p-5">
            <SectionHeader
                title="Payment progress"
                :description="`Salary payment completion for ${activePeriod.label}`"
            >
                <template #actions>
                    <p class="text-xl font-bold tabular-nums text-green-600">
                        {{ formatPercentage(paymentPercentage) }}
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
                        {{ formatCurrency(activePeriod.paidAmount) }}
                    </strong>
                </span>

                <span>
                    Pending:
                    <strong class="font-medium text-foreground">
                        {{ formatCurrency(activePeriod.pendingAmount) }}
                    </strong>
                </span>
            </div>
        </div>

        <!-- Breakdown and employee status -->
        <div class="grid gap-4 xl:grid-cols-2">
            <!-- Salary breakdown -->
            <div class="rounded-xl border bg-card">
                <div class="border-b p-5">
                    <SectionHeader
                        title="Salary breakdown"
                        :description="`Salary components for ${activePeriod.label}`"
                    />
                </div>

                <div class="divide-y px-5">
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
                            {{ formatCurrency(item.amount) }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 border-t bg-muted/30 px-5 py-4">
                    <span class="text-sm font-semibold">Final payable</span>
                    <span class="text-lg font-bold tabular-nums">
                        {{ formatCurrency(activePeriod.totalPayable) }}
                    </span>
                </div>
            </div>

            <!-- Employee payment status -->
            <div class="rounded-xl border bg-card">
                <div class="flex items-center justify-between gap-3 border-b p-5">
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
                    class="max-h-[360px] divide-y overflow-y-auto"
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
                                {{ formatCurrency(employee.amount) }}
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
                            No salary records found
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            There are no employee salary records for this period.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly comparison -->
        <div class="rounded-xl border bg-card">
            <div class="border-b p-5">
                <SectionHeader
                    title="Monthly salary comparison"
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
                        :key="period.label"
                        class="min-w-0"
                    >
                        <div class="mb-3 text-center">
                            <p class="text-sm font-semibold">
                                {{ period.label }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ formatCurrency(period.totalPayable) }}
                            </p>
                        </div>

                        <div class="flex h-48 items-end justify-center border-b border-dashed border-border">
                            <div
                                class="flex w-20 max-w-full flex-col justify-end overflow-hidden rounded-t-md bg-muted"
                                :style="{ height: getBarHeight(period.totalPayable) }"
                                :title="`Total payable: ${formatCurrency(period.totalPayable)}`"
                            >
                                <div
                                    class="flex min-h-0 flex-col-reverse overflow-hidden"
                                    :style="{ height: `${getPaymentRatio(period)}%` }"
                                >
                                    <div
                                        class="min-h-0 flex-1 bg-green-500"
                                        :title="`Paid: ${formatCurrency(period.paidAmount)}`"
                                    />
                                </div>

                                <div
                                    class="min-h-0 flex-1 bg-amber-500"
                                    :title="`Pending: ${formatCurrency(period.pendingAmount)}`"
                                />
                            </div>
                        </div>

                        <div class="mt-3 space-y-1 text-center">
                            <p class="text-xs text-green-600">
                                Paid: {{ formatCurrency(period.paidAmount) }}
                            </p>
                            <p class="text-xs text-amber-600">
                                Pending: {{ formatCurrency(period.pendingAmount) }}
                            </p>
                        </div>
                    </div>
                </div>

                <p class="text-center text-xs text-muted-foreground">
                    Comparison is based on salary periods, not payment dates.
                </p>
            </div>
        </div>
    </div>
</template>
