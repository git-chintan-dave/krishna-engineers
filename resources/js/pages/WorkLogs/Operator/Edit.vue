<script lang="ts" setup>
import {router, useForm} from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import OperatorWorkLogForm from '@/pages/WorkLogs/Operator/components/OperatorWorkLogForm.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {operatorWorkLogPage} from '@/config/operatorWorkLogs';
import {index as operatorWorkLogs, update} from '@/routes/operator';

import type {OperatorWorkLog, OperatorWorkLogFormData, OperatorWorkLogPartSetupOption,} from '@/types/operatorWorkLog';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

type LastReport = {
    shift: string | null;
    machine_id: number;
    part_id: number;
    setup_id: number;
};

const props = defineProps<{
    operatorWorkLog: OperatorWorkLog;
    partSetups: OperatorWorkLogPartSetupOption[];
    currentUserId: number;
    canSelectOperator: boolean;
    lastReports: Record<string, LastReport>;
}>();

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('edit', {
            resource: operatorWorkLogPage.title,
            resourceUrl: operatorWorkLogs().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm<OperatorWorkLogFormData>({
    id: props.operatorWorkLog.id,
    user_id: props.operatorWorkLog.user.id,
    work_date: props.operatorWorkLog.work_date,
    shift: props.operatorWorkLog.shift,
    machine_id: props.operatorWorkLog.machine.id,
    part_id: props.operatorWorkLog.part.id,
    setup_id: props.operatorWorkLog.setup.id,
    part_setup_id: props.operatorWorkLog.part_setup_id,
    machine_cycle_time: props.operatorWorkLog.machine_cycle_time,
    actual_cycle_time: props.operatorWorkLog.actual_cycle_time,
    quantity: props.operatorWorkLog.quantity,
    rejection_quantity: props.operatorWorkLog.rejection_quantity,
    remarks: props.operatorWorkLog.remarks ?? '',
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form.put(update(props.operatorWorkLog.uuid).url);
};

const handleCancel = () => {
    router.visit(operatorWorkLogs().url);
};
</script>

<template>
    <FormLayout
        :description="operatorWorkLogPage.form.editDescription"
        :title="operatorWorkLogPage.form.editTitle"
    >
        <OperatorWorkLogForm
            :can-select-operator="props.canSelectOperator"
            :current-user-id="props.currentUserId"
            :errors="form.errors"
            :form="form"
            :is-create="false"
            :last-reports="props.lastReports"
            :part-setups="props.partSetups"
            :processing="form.processing"
            :submit-label="operatorWorkLogPage.form.editSubmitLabel"
            @cancel="handleCancel"
            @submit="handleSubmit"
        />
    </FormLayout>
</template>
