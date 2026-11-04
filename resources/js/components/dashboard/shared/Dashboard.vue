<script lang="ts" setup>
import {computed, ref, watch} from 'vue';
import {router} from '@inertiajs/vue3';
import {Boxes, Factory, Users, Wallet} from '@lucide/vue';

import AppPageHeader from '@/components/common/AppPageHeader.vue';
import SegmentedTabs, {type SegmentedTabItem} from '@/components/common/SegmentedTabs.vue';

import ProductionTab from './productionTab.vue';
import AttendanceTab from './attendanceTab.vue';
import StocksTab from './stocksTab.vue';
import SalaryTab from '../SuperAdmin/salaryTab.vue';

import type {
    ManagementAttendanceDashboard,
    ProductionDashboard,
    SalaryDashboard,
    StockDashboard,
} from '@/types/dashboard';

type Tab = 'production' | 'attendance' | 'salary' | 'stocks';

const props = withDefaults(
    defineProps<{
        showProduction: boolean;
        showSalary: boolean;
        productionDashboard?: ProductionDashboard | null;
        attendanceDashboard?: ManagementAttendanceDashboard | null;
        salaryDashboard?: SalaryDashboard | null;
        rawMaterialStock?: StockDashboard | null;
        toolStock?: StockDashboard | null;
    }>(),
    {
        productionDashboard: undefined,
        attendanceDashboard: undefined,
        salaryDashboard: undefined,
        rawMaterialStock: undefined,
        toolStock: undefined,
    },
);

const activeTab = ref<Tab>(props.showProduction ? 'production' : 'attendance');

// Production loading state
const productionLoaded = ref(false);
const productionLoading = ref(false);

// Attendance loading state
const attendanceLoaded = ref(false);
const attendanceLoading = ref(false);

// Salary loading state
const salaryLoaded = ref(false);
const salaryLoading = ref(false);

// Stock loading state
const stockLoaded = ref(false);
const stockLoading = ref(false);

const tabs = computed<SegmentedTabItem<Tab>[]>(() => [
    ...(props.showProduction
        ? [{
            value: 'production' as const,
            label: 'Production',
            icon: Factory,
        }]
        : []),
    {
        value: 'attendance',
        label: 'Attendance',
        icon: Users,
    },
    ...(props.showSalary
        ? [{
            value: 'salary' as const,
            label: 'Salary',
            icon: Wallet,
        }]
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
    switch (activeTab.value) {
        case 'production':
            return {
                productionDashboard: props.productionDashboard,
            };

        case 'attendance':
            return {
                attendanceDashboard: props.attendanceDashboard,
            };

        case 'salary':
            return {
                salaryDashboard: props.salaryDashboard,
            };

        case 'stocks':
            return {
                rawMaterialStock: props.rawMaterialStock,
                toolStock: props.toolStock,
            };

        default:
            return {};
    }
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
    activeTab,
    (tab) => {
        if (tab === 'production' && !productionLoaded.value && !productionLoading.value) {
            productionLoading.value = true;

            router.reload({
                only: ['productionDashboard'],
                onSuccess: () => {
                    productionLoaded.value = true;
                },
                onFinish: () => {
                    productionLoading.value = false;
                },
            });
        }

        if (tab === 'attendance' && !attendanceLoaded.value && !attendanceLoading.value) {
            attendanceLoading.value = true;

            router.reload({
                only: ['attendanceDashboard'],
                onSuccess: () => {
                    attendanceLoaded.value = true;
                },
                onFinish: () => {
                    attendanceLoading.value = false;
                },
            });
        }

        if (tab === 'salary' && !salaryLoaded.value && !salaryLoading.value) {
            salaryLoading.value = true;

            router.reload({
                only: ['salaryDashboard'],
                onSuccess: () => {
                    salaryLoaded.value = true;
                },
                onFinish: () => {
                    salaryLoading.value = false;
                },
            });
        }

        if (tab === 'stocks' && !stockLoaded.value && !stockLoading.value) {
            stockLoading.value = true;

            router.reload({
                only: ['rawMaterialStock', 'toolStock'],
                onSuccess: () => {
                    stockLoaded.value = true;
                },
                onFinish: () => {
                    stockLoading.value = false;
                },
            });
        }
    },
    {immediate: true},
);

// Keep selected tab valid when access changes.
watch(
    () => [props.showProduction, props.showSalary] as const,
    ([showProduction, showSalary]) => {
        if (!showProduction && activeTab.value === 'production') {
            activeTab.value = 'attendance';
        }

        if (!showSalary && activeTab.value === 'salary') {
            activeTab.value = showProduction
                ? 'production'
                : 'attendance';
        }
    },
);
</script>

<template>
    <main class="w-full min-w-0 max-w-full space-y-5 p-4 pt-0 md:px-6 md:pb-5">
        <div
            class="sticky top-(--app-header-height,0px) z-30 flex min-w-0 flex-col border-b border-border bg-background/95 backdrop-blur-md md:flex-row md:items-center"
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
