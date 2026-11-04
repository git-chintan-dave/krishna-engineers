<script lang="ts" setup>
import {computed} from 'vue';
import {usePage} from '@inertiajs/vue3';

import Dashboard from '../shared/Dashboard.vue';
import {ManagementAttendanceDashboard, ProductionDashboard, SalaryDashboard, StockDashboard} from '@/types/dashboard';

const page = usePage<{
    productionDashboard?: ProductionDashboard | null;
    attendanceDashboard?: ManagementAttendanceDashboard | null;
    salaryDashboard?: SalaryDashboard | null;
    rawMaterialStock?: StockDashboard | null;
    toolStock?: StockDashboard | null;
}>();

const productionDashboard  = computed(() => page.props.productionDashboard );
const attendanceDashboard  = computed(() => page.props.attendanceDashboard );
const salaryDashboard = computed(() => page.props.salaryDashboard);
const rawMaterialStock = computed(() => page.props.rawMaterialStock);
const toolStock = computed(() => page.props.toolStock);
</script>

<template>
    <Dashboard
        :show-production="true"
        :production-dashboard="productionDashboard"
        :attendance-dashboard="attendanceDashboard"
        :show-salary="true"
        :salary-dashboard="salaryDashboard"
        :raw-material-stock="rawMaterialStock"
        :tool-stock="toolStock"
    />
</template>
