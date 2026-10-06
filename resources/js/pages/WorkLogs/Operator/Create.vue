<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';

import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';

import { operatorWorkLogPage } from '@/config/operatorWorkLogs';

import {
    index as operatorWorkLogs,
    store,
} from '@/routes/operator';

import OperatorWorkLogForm from '@/pages/WorkLogs/Operator/components/OperatorWorkLogForm.vue';

import type {
    OperatorWorkLogFormData,
    OperatorWorkLogPartSetupOption,
} from '@/types/operatorWorkLog';

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
    partSetups: OperatorWorkLogPartSetupOption[];
    currentUserId: number;
    canSelectOperator: boolean;
    lastReports: Record<
        string,
        LastReport
    >;
}>();

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('create', {
            resource: operatorWorkLogPage.title,
            resourceUrl:
            operatorWorkLogs().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form =
    useForm<OperatorWorkLogFormData>({
        user_id:
            props.canSelectOperator
                ? null
                : props.currentUserId,

        work_date: new Date()
            .toISOString()
            .slice(0, 10),

        shift: null,

        machine_id: null,
        part_id: null,
        setup_id: null,
        part_setup_id: null,

        machine_cycle_time: 0,
        actual_cycle_time: 0,

        quantity: 1,
        rejection_quantity: 0,

        remarks: '',
    });

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form.post(store().url);
};

const handleCancel = () => {
    router.visit(
        operatorWorkLogs().url,
    );
};
</script>

<template>
    <FormLayout
        :title="
            operatorWorkLogPage.form
                .createTitle
        "
        :description="
            operatorWorkLogPage.form
                .createDescription
        "
    >
        <OperatorWorkLogForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="
                operatorWorkLogPage.form
                    .createSubmitLabel
            "
            :part-setups="
                props.partSetups
            "
            :current-user-id="
                props.currentUserId
            "
            :can-select-operator="
                props.canSelectOperator
            "
            :last-reports="
                props.lastReports
            "
            :is-create="true"
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
