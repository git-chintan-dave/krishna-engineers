<script lang="ts" setup>
import {computed} from 'vue';
import {usePage} from '@inertiajs/vue3';

import BandsawDashboard from '@/components/dashboard/BandsawDashboard.vue';
import OperatorDashboard from '@/components/dashboard/OperatorDashboard.vue';
import SuperAdminDashboard from '@/components/dashboard/SuperAdminDashboard.vue';

const page = usePage();

const dashboardComponent = computed(() => {
    const role = page.props.auth.user.role;

    switch (role) {
        case 'super_admin':
            return SuperAdminDashboard;

        case 'operator':
            return OperatorDashboard;

        case 'bandsaw':
            return BandsawDashboard;

        default:
            return null;
    }
});
</script>

<template>
    <component :is="dashboardComponent" v-if="dashboardComponent" />
</template>
