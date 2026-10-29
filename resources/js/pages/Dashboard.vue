<script lang="ts" setup>
import {computed, ref, shallowRef, watch} from 'vue';
import {router, usePage} from '@inertiajs/vue3';

import SuperAdminDashboard from '@/components/dashboard/SuperAdminDashboard.vue';
import SupervisorDashboard from '@/components/dashboard/SupervisorDashboard.vue';
import OperatorDashboard from '@/components/dashboard/OperatorDashboard.vue';
import BandsawDashboard from '@/components/dashboard/BandsawDashboard.vue';
import DeburringPackagingDashboard from '@/components/dashboard/DeburringPackagingDashboard.vue';

import type {DashboardPayload, DashboardRole} from '@/types/dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
            },
        ],
    },
});

type DashboardSection =
    | 'salaryOverview'
    | 'attendanceOverview'
    | 'inventoryAlerts'
    | 'productionOverview'
    | 'todayShift'
    | 'monthlyAttendance'
    | 'stats'
    | 'recentOperatorLogs'
    | 'recentBandsawLogs';

const page = usePage();

const pageProps = computed(() => page.props as Record<string, any>);

const role = computed(
    () => String(pageProps.value.auth?.user?.role ?? '') as DashboardRole,
);

const roleDashboards = {
    super_admin: SuperAdminDashboard,
    supervisor: SupervisorDashboard,
    operator: OperatorDashboard,
    bandsaw: BandsawDashboard,
    deburring_packaging: DeburringPackagingDashboard,
} satisfies Partial<Record<DashboardRole, unknown>>;

const dashboardComponent = computed(
    () => roleDashboards[role.value] ?? null,
);

const sectionKeys = computed<DashboardSection[]>(() => {
    const common: DashboardSection[] = [
        'todayShift',
        'monthlyAttendance',
    ];

    switch (role.value) {
        case 'super_admin':
            return [
                'salaryOverview',
                'attendanceOverview',
                'inventoryAlerts',
                'productionOverview',
            ];

        case 'supervisor':
            return [
                ...common,
                'stats',
                'attendanceOverview',
                'recentOperatorLogs',
                'recentBandsawLogs',
            ];

        case 'operator':
            return [...common, 'stats', 'recentOperatorLogs'];

        case 'bandsaw':
            return [...common, 'stats', 'recentBandsawLogs'];

        case 'deburring_packaging':
            return common;

        default:
            return [];
    }
});

const dashboardData = computed<DashboardPayload | undefined>(() => {
    const props = pageProps.value;

    if (!sectionKeys.value.some((key) => props[key] !== undefined)) {
        return undefined;
    }

    const sections = [
        props.recentOperatorLogs,
        props.recentBandsawLogs,
    ].filter((section) => section !== undefined);

    return {
        title: props.title,
        description: props.description,
        todayShift: props.todayShift,
        monthlyAttendance: props.monthlyAttendance,
        salaryOverview: props.salaryOverview,
        inventoryAlerts: props.inventoryAlerts,
        productionOverview: props.productionOverview,
        attendanceOverview: props.attendanceOverview,
        stats: props.stats,
        sections,
    } as DashboardPayload;
});

const loading = ref(true);
const refreshing = ref(false);
const loadedSections = shallowRef(new Set<DashboardSection>());

watch(
    [role, pageProps, sectionKeys],
    ([currentRole, props, keys], previous) => {
        if (previous && currentRole !== previous[0]) {
            loadedSections.value = new Set();
            loading.value = true;
        }

        const loaded = new Set(loadedSections.value);

        for (const key of keys) {
            if (props[key] !== undefined) {
                loaded.add(key);
            }
        }

        loadedSections.value = loaded;

        if (keys.length === 0 || keys.every((key) => loaded.has(key))) {
            loading.value = false;
        }
    },
    {immediate: true},
);

function refreshSection(section: string): void {
    if (!sectionKeys.value.includes(section as DashboardSection)) {
        return;
    }

    router.reload({
        only: [section],
        preserveState: true,
    });
}

function refreshDashboard(): void {
    router.reload({
        only: sectionKeys.value,
        preserveState: true,
        onStart: () => {
            refreshing.value = true;
        },
        onFinish: () => {
            refreshing.value = false;
            loading.value = false;
        },
    });
}
</script>

<template>
    <main class="w-full min-w-0 space-y-6 overflow-x-hidden p-4 md:p-6">
        <section v-if="dashboardComponent" class="min-w-0">
            <component
                :is="dashboardComponent"
                :attendance-overview="pageProps.attendanceOverview"
                :dashboard-data="dashboardData"
                :inventory-alerts="pageProps.inventoryAlerts"
                :loading="loading"
                :production-overview="pageProps.productionOverview"
                :refreshing="refreshing"
                :salary-overview="pageProps.salaryOverview"
                @refresh="refreshDashboard"
                @refresh-section="refreshSection"
            />
        </section>
        <section
            v-else
            class="min-w-0 rounded-lg border border-border bg-card p-6"
        >
            <h2 class="text-lg font-semibold">Dashboard unavailable</h2>
            <p class="mt-2 text-sm text-muted-foreground">
                No dashboard is configured for your account role.
            </p>
        </section>
    </main>
</template>
