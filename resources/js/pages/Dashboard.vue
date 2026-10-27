<script lang="ts" setup>
import {computed, ref, watch} from 'vue';
import {router, usePage} from '@inertiajs/vue3';

import SupervisorDashboard from '@/components/dashboard/SupervisorDashboard.vue';
import OperatorDashboard from '@/components/dashboard/OperatorDashboard.vue';
import BandsawDashboard from '@/components/dashboard/BandsawDashboard.vue';
import DeburringPackagingDashboard from '@/components/dashboard/DeburringPackagingDashboard.vue';

import type {DashboardPayload, DashboardRole} from '@/types/dashboard';

const page = usePage();

const roleDashboards = {
    supervisor: SupervisorDashboard,
    operator: OperatorDashboard,
    bandsaw: BandsawDashboard,
    deburring_packaging: DeburringPackagingDashboard,
} satisfies Partial<Record<DashboardRole, unknown>>;

const pageProps = computed(() => page.props as Record<string, any>);

const role = computed(
    () => String(pageProps.value.auth?.user?.role ?? '') as DashboardRole,
);

const dashboardComponent = computed(() => roleDashboards[role.value] ?? null);

const sectionKeys = computed<string[]>(() => {
    const common = ['todayShift', 'monthlyAttendance'];

    switch (role.value) {
        case 'supervisor':
            return [
                ...common,
                'stats',
                'attendanceOverview',
                'recentOperatorLogs',
                'recentBandsawLogs',
            ];

        case 'operator':
            return [
                ...common,
                'stats',
                'recentOperatorLogs',
            ];

        case 'bandsaw':
            return [
                ...common,
                'stats',
                'recentBandsawLogs',
            ];

        case 'deburring_packaging':
            return common;

        default:
            return [];
    }
});

/**
 * Reassemble the existing payload expected by role dashboard components.
 * Backend sections are supplied as independent top-level deferred props.
 */
const dashboardData = computed<DashboardPayload | undefined>(() => {
    const props = pageProps.value;

    const hasDashboardProps = sectionKeys.value.some(
        (key) => key in props,
    );

    if (!hasDashboardProps) {
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
        stats: props.stats,
        attendanceOverview: props.attendanceOverview,
        sections,
    } as DashboardPayload;
});

const loading = ref(true);
const refreshing = ref(false);
const loadedSections = ref(new Set<string>());

watch(
    [pageProps, sectionKeys],
    ([props, keys]) => {
        for (const key of keys) {
            if (key in props && props[key] !== undefined) {
                loadedSections.value.add(key);
            }
        }

        if (keys.length === 0 || keys.every((key) => loadedSections.value.has(key))) {
            loading.value = false;
        }
    },
    {immediate: true},
);

function refreshSection(section: string): void {
    if (!sectionKeys.value.includes(section)) {
        return;
    }

    router.reload({
        only: [section],
        preserveScroll: true,
        preserveState: true,
    });
}

function refreshDashboard(): void {
    router.reload({
        only: sectionKeys.value,
        preserveScroll: true,
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
    <component
        :is="dashboardComponent"
        v-if="dashboardComponent"
        :dashboard-data="dashboardData"
        :loading="loading"
        :refreshing="refreshing"
        @refresh="refreshDashboard"
        @refresh-section="refreshSection"
    />

    <section v-else class="rounded-lg border border-border bg-card p-6">
        <h1 class="text-lg font-semibold">Dashboard unavailable</h1>
        <p class="mt-2 text-sm text-muted-foreground">
            No dashboard is configured for your account role.
        </p>
    </section>
</template>
