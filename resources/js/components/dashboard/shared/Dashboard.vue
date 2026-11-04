<script lang="ts" setup>
import {computed, ref, watch} from 'vue';
import {router} from '@inertiajs/vue3';
import {Boxes, Factory, Users, Wallet} from '@lucide/vue';

import AppPageHeader from '@/components/common/AppPageHeader.vue';
import SegmentedTabs, {type SegmentedTabItem,} from '@/components/common/SegmentedTabs.vue';

import ProductionTab from './productionTab.vue';
import AttendanceTab from './attendanceTab.vue';
import StocksTab from './stocksTab.vue';
import SalaryTab from '../SuperAdmin/salaryTab.vue';

import type {SalaryDashboard, StockDashboard} from '@/types/dashboard';

type Tab = 'production' | 'attendance' | 'salary' | 'stocks';

const props = withDefaults(
    defineProps<{
        showSalary: boolean;
        salaryDashboard?: SalaryDashboard | null;
        rawMaterialStock?: StockDashboard | null;
        toolStock?: StockDashboard | null;
    }>(),
    {
        salaryDashboard: undefined,
        rawMaterialStock: undefined,
        toolStock: undefined,
    },
);

const activeTab = ref<Tab>('production');

// salary loading state
const salaryLoaded = ref(false);
const salaryLoading = ref(false);

// Stock loading state
const stockLoaded = ref(false);
const stockLoading = ref(false);

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
    switch (activeTab.value) {
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

// Load stock data when the Stocks tab is opened for the first time.
watch(activeTab, (tab) => {
    if (tab === 'salary') {
        if (
            !props.showSalary ||
            salaryLoaded.value ||
            salaryLoading.value
        ) {
            return;
        }

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

        return;
    }

    if (tab === 'stocks') {
        if (stockLoaded.value || stockLoading.value) {
            return;
        }

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
});

// Keep the selected tab valid when salary access changes.
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
    <main class="w-full min-w-0 max-w-full space-y-5 p-4 md:px-6 md:py-5">
        <!-- Sticky dashboard header and tabs -->
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
