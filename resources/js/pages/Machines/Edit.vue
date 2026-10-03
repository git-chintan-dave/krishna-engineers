<script setup lang="ts">
import {router, useForm} from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import {machinePage} from '@/config/machines';
import MachineForm from '@/pages/Machines/components/MachineForm.vue';
import {index as machines, update as updateMachine,} from '@/routes/machines';
import type {Machine, MachineFormData,} from '@/types/machine';
import {useCrudBreadcrumbs} from "@/composables/useCrudBreadcrumbs";

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    machine: Machine;
}>();

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('edit', {
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
    name: props.machine.name,
    machine_number: props.machine.machine_number ?? '',
    status: props.machine.status === 'active',
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form.transform((data) => ({
        ...data,
        status: data.status ? 'active' : 'inactive',
    })).put(updateMachine(props.machine.uuid).url);
};

const handleCancel = () => {
    router.visit(machines());
};
</script>

<template>
    <FormLayout
        :title="machinePage.form.editTitle"
        :description="machinePage.form.editDescription"
    >
        <MachineForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="machinePage.form.editSubmitLabel"
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
