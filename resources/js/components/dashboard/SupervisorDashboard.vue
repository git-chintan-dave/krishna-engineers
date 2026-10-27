<script lang="ts" setup>
import DashboardContent from './DashboardContent.vue';
import type {DashboardPayload} from '@/types/dashboard';

defineProps<{
    dashboardData?: DashboardPayload;
    loading: boolean;
    refreshing: boolean;
}>();

defineEmits<{
    refresh: [];
}>();
</script>

<template>
    <DashboardContent
        role-label="Supervisor"
        :dashboard-data="dashboardData"
        :loading="loading"
        :refreshing="refreshing"
        @refresh="$emit('refresh')"
    />
</template>
