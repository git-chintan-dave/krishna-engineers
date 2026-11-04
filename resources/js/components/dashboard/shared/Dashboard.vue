<script lang="ts" setup>
import {computed, ref, watch} from 'vue';
import {Boxes, Factory, Users, Wallet} from '@lucide/vue';

import AppPageHeader from '@/components/common/AppPageHeader.vue';
import SegmentedTabs, {type SegmentedTabItem,} from '@/components/common/SegmentedTabs.vue';

import ProductionTab from './productionTab.vue';
import AttendanceTab from './attendanceTab.vue';
import StocksTab from './stocksTab.vue';
import SalaryTab from '../SuperAdmin/salaryTab.vue';

type Tab = 'production' | 'attendance' | 'salary' | 'stocks';

interface SalaryPeriod {
    label: string;
    startDate: string;
    endDate: string;
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
    employees: {
        id: number | string;
        name: string;
        amount: number;
        status: 'paid' | 'pending';
        paidAt?: string | null;
    }[];
}

const props = defineProps<{
    showSalary: boolean;
    salaryDashboard: {
        currentPeriod: SalaryPeriod;
        previousPeriod: SalaryPeriod;
    };
}>();

const activeTab = ref<Tab>('production');

const tabs = computed<SegmentedTabItem<Tab>[]>(() => [
    {
        value: 'production',
        label: 'Production',
        icon: Factory,
    },
    {
        value: 'attendance',
        label: 'Attendance',
        icon: Users,
    },
    ...(props.showSalary
        ? [
            {
                value: 'salary' as const,
                label: 'Salary',
                icon: Wallet,
            },
        ]
        : []),
    {
        value: 'stocks',
        label: 'Stocks',
        icon: Boxes,
    },
]);

const tabComponents = {
    production: ProductionTab,
    attendance: AttendanceTab,
    salary: SalaryTab,
    stocks: StocksTab,
};

const activeComponent = computed(
    () => tabComponents[activeTab.value],
);

const activeComponentProps = computed(() => {
    if (activeTab.value === 'salary') {
        return props.salaryDashboard;
    }

    return {};
});

const tabDescription = computed(() => {
    const descriptions: Record<Tab, string> = {
        production: 'Monitor factory output, quality and machine performance.',
        attendance: 'Monitor employee attendance and shift activity.',
        salary: 'Track employee salary records and payment progress.',
        stocks: 'Monitor raw materials, tools and inventory availability.',
    };

    return descriptions[activeTab.value];
});

watch(
    () => props.showSalary,
    (showSalary) => {
        if (!showSalary && activeTab.value === 'salary') {
            activeTab.value = 'production';
        }
    },
);
</script>

<template>
    <main
        class="w-full min-w-0 max-w-full space-y-5 p-4 md:px-6 md:py-5"
    >
        <!-- Sticky dashboard header and tabs -->
        <div
            class="sticky top-(--app-header-height,0px) z-30 border-b border-border bg-background/95 backdrop-blur-md flex flex-col md:flex-row md:items-center min-w-0"
        >
            <AppPageHeader
                title="Administration dashboard"
                :description="tabDescription"
            />

            <div class="mb-4 md:mb-0">
                <SegmentedTabs
                    v-model="activeTab"
                    :items="tabs"
                    aria-label="Dashboard sections"
                    variant="primary"
                />
            </div>
        </div>

        <!-- Selected dashboard tab -->
        <section class="min-w-0">
            <Transition
                name="dashboard-tab"
                mode="out-in"
            >
                <component
                    :is="activeComponent"
                    :key="activeTab"
                    v-bind="activeComponentProps"
                />
            </Transition>
        </section>
    </main>
</template>

<style scoped>
.dashboard-tab-enter-active,
.dashboard-tab-leave-active {
    transition:
        opacity 180ms ease,
        transform 180ms ease;
}

.dashboard-tab-enter-from {
    opacity: 0;
    transform: translateY(8px);
}

.dashboard-tab-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

@media (prefers-reduced-motion: reduce) {
    .dashboard-tab-enter-active,
    .dashboard-tab-leave-active {
        transition: none;
    }
}
</style>
