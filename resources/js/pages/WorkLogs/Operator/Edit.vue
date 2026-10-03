<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import OperatorWorkLogForm from '@/pages/WorkLogs/Operator/components/OperatorWorkLogForm.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { operatorWorkLogPage } from '@/config/operatorWorkLogs';
import {
    index as operatorWorkLogs,
    update,
} from '@/routes/operator';
import type {
    OperatorWorkLog,
    OperatorWorkLogFormData,
} from '@/types/operatorWorkLog';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

type SelectOption<T = number | string> = {
    value: T;
    label: string;
};

type PartSetupOption = {
    id: number;
    part_id: number;
    setup_id: number;
    machine_cycle_time_seconds: number;
    actual_cycle_time_seconds: number;
};

type LastReport = {
    shift: string | null;
    machine_id: number;
    part_id: number;
    setup_id: number;
};

const props = defineProps<{
    operatorWorkLog: OperatorWorkLog;

    operators: SelectOption<number>[];
    machines: SelectOption<number>[];
    parts: SelectOption<number>[];
    setups: SelectOption<number>[];
    shifts: SelectOption<string>[];
    partSetups: PartSetupOption[];

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
    form.put(update(props.operatorWorkLog.id).url);
};

const handleCancel = () => {
    router.visit(operatorWorkLogs().url);
};
</script>

<template>
    <FormLayout
        :title="operatorWorkLogPage.form.editTitle"
        :description="operatorWorkLogPage.form.editDescription"
    >
        <OperatorWorkLogForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="operatorWorkLogPage.form.editSubmitLabel"
            :operators="props.operators"
            :machines="props.machines"
            :parts="props.parts"
            :setups="props.setups"
            :shifts="props.shifts"
            :part-setups="props.partSetups"
            :current-user-id="props.currentUserId"
            :can-select-operator="props.canSelectOperator"
            :last-reports="props.lastReports"
            :is-create="false"
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
