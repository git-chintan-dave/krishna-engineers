<script lang="ts" setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import Dashboard from '../shared/Dashboard.vue';
import type {SalaryDashboard, StockDashboard} from '@/types/dashboard';

const page = usePage<{
    salaryDashboard?: SalaryDashboard | null;
    rawMaterialStock?: StockDashboard | null;
    toolStock?: StockDashboard | null;
}>();

const salaryDashboard = computed(() => page.props.salaryDashboard);
const rawMaterialStock = computed(() => page.props.rawMaterialStock);
const toolStock = computed(() => page.props.toolStock);
</script>

<template>
    <Dashboard
        :show-salary="true"
        :salary-dashboard="salaryDashboard"
        :raw-material-stock="rawMaterialStock"
        :tool-stock="toolStock"
    />
</template>
