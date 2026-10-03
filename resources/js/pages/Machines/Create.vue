<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { machinePage } from '@/config/machines';
import MachineForm from '@/pages/Machines/components/MachineForm.vue';
import {
    index as machines,
    store,
} from '@/routes/machines';
import type { MachineFormData } from '@/types/machine';

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('create', {
            resource: machinePage.title,
            resourceUrl: machines().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm<MachineFormData>({
    name: '',
    machine_number: '',
    status: true,
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form
        .transform((data) => ({
            ...data,
            status: data.status
                ? 'active'
                : 'inactive',
        }))
        .post(store().url);
};

const handleCancel = () => {
    router.visit(machines().url);
};
</script>

<template>
    <FormLayout
        :title="machinePage.form.createTitle"
        :description="
            machinePage.form.createDescription
        "
    >
        <MachineForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="
                machinePage.form.createSubmitLabel
            "
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
