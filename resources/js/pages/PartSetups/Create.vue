<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { partSetupPage } from '@/config/partSetups';
import PartSetupForm from '@/pages/PartSetups/components/PartSetupForm.vue';
import {
    index as partSetups,
    store,
} from '@/routes/part-setups';
import type {
    PartSetupFormData,
    PartSetupOption,
} from '@/types/partSetup';
import MachineForm from "@/pages/Machines/components/MachineForm.vue";

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('create', {
            resource: partSetupPage.title,
            resourceUrl: partSetups().url,
        }),
    },
});

const form = useForm<PartSetupFormData>({
    part_id: null,
    setup_id: null,
    image: null,
    remove_image: false,
    machine_cycle_time: '',
    actual_cycle_time: '',
});

const handleSubmit = () => {
    form
        .transform((data) => ({
            ...data,
            part_id: data.part_id
                ? Number(data.part_id)
                : null,
            setup_id: data.setup_id
                ? Number(data.setup_id)
                : null,
        }))
        .post(store().url);
};

const handleCancel = () => {
    router.visit(partSetups().url);
};
</script>

<template>
    <FormLayout
        :title="partSetupPage.form.createTitle"
        :description="
            partSetupPage.form
                .createDescription
        "
    >
        <PartSetupForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="
                partSetupPage.form
                    .createSubmitLabel
            "
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
