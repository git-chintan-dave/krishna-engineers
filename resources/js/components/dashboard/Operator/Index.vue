<script lang="ts" setup>
import {computed} from 'vue';
import {usePage} from '@inertiajs/vue3';

import EmployeeDashboard from '@/components/dashboard/employee/EmployeeDashboard.vue';
import type {EmployeeAttendanceDashboard} from '@/types/dashboard';

const page = usePage<{
    attendanceDashboard?: EmployeeAttendanceDashboard | null;
}>();

const attendanceDashboard = computed(
    () => page.props.attendanceDashboard,
);
</script>

<template>
    <EmployeeDashboard
        :attendance-dashboard="attendanceDashboard"
    />
</template>
