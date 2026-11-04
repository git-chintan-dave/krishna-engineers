<script lang="ts" setup>
import {computed} from 'vue';
import {usePage} from '@inertiajs/vue3';
import {LayoutDashboard} from '@lucide/vue';

import EmptyState from '@/components/common/EmptyState.vue';

import SuperAdminDashboard from '@/components/dashboard/SuperAdmin/Index.vue';
import SupervisorDashboard from '@/components/dashboard/Supervisor/Index.vue';

import OperatorDashboard from '@/components/dashboard/Operator/Index.vue';
import BandsawDashboard from '@/components/dashboard/Bandsaw/Index.vue';
import DeburringPackagingDashboard from '@/components/dashboard/DeburringPackaging/Index.vue';

type UserRole =
    | 'super_admin'
    | 'supervisor'
    | 'operator'
    | 'bandsaw'
    | 'deburring_packaging';

const page = usePage<{
    auth: {
        user: {
            role: UserRole | string;
        };
    };
}>();

const dashboardComponents = {
    super_admin: SuperAdminDashboard,
    supervisor: SupervisorDashboard,
    operator: OperatorDashboard,
    bandsaw: BandsawDashboard,
    deburring_packaging: DeburringPackagingDashboard,
} as const;

const dashboardComponent = computed(
    () => dashboardComponents[
        page.props.auth.user.role as UserRole
        ] ?? null,
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
            },
        ],
    },
});
</script>

<template>
    <component
        :is="dashboardComponent"
        v-if="dashboardComponent"
    />

    <EmptyState
        v-else
        title="Dashboard unavailable"
        description="Your account does not have an available dashboard."
        :icon="LayoutDashboard"
    />
</template>
