<script lang="ts" setup>
import {computed, ref, watch} from 'vue';
import {router, usePage} from '@inertiajs/vue3';

import BandsawDashboard from '@/components/dashboard/BandsawDashboard.vue';
import OperatorDashboard from '@/components/dashboard/OperatorDashboard.vue';
import SuperAdminDashboard from '@/components/dashboard/SuperAdminDashboard.vue';
import {TodayShift} from "@/types/dashboard";


const page = usePage<{
    auth: {
        user: {
            role: string;
        };
    };
    todayShift?: TodayShift;
}>();

const attendanceLoading = ref(!page.props.todayShift);

watch(
    () => page.props.todayShift,
    (attendance) => {
        if (attendance) {
            attendanceLoading.value = false;
        }
    },
);

const dashboardComponent = computed(() => {
    switch (page.props.auth.user.role) {
        case 'super_admin':
        case 'supervisor':
            return SuperAdminDashboard;

        case 'operator':
            return OperatorDashboard;

        case 'bandsaw':
            return BandsawDashboard;

        default:
            return null;
    }
});

function refreshAttendance() {
    router.reload({
        only: ['todayShift'],
        preserveScroll: true,
        preserveState: true,
        onStart: () => {
            attendanceLoading.value = true;
        },
        onFinish: () => {
            attendanceLoading.value = false;
        },
    });
}
</script>

<template>
    <component
        :is="dashboardComponent"
        v-if="dashboardComponent"
        :today-shift="page.props.todayShift"
        :attendance-loading="attendanceLoading"
        @refresh-attendance="refreshAttendance"
    />
</template>
