<script setup lang="ts">
import {router, useForm} from '@inertiajs/vue3';
import {computed} from 'vue';

import FormLayout from '@/components/layouts/FormLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {partSetupPage} from '@/config/partSetups';
import PartSetupForm from '@/pages/PartSetups/components/PartSetupForm.vue';
import {index as partSetups, update,} from '@/routes/part-setups';
import type {PartSetup, PartSetupFormData, PartSetupOption,} from '@/types/partSetup';

const props = defineProps<{
    partSetup: PartSetup;
}>();

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('edit', {
            resource: partSetupPage.title,
            resourceUrl: partSetups().url,
        }),
    },
});

const form = useForm<PartSetupFormData>({
    part_id: props.partSetup.part_id,
    setup_id: props.partSetup.setup_id,
    image: null,
    remove_image: false,
    machine_cycle_time: props.partSetup.machine_cycle_time,
    actual_cycle_time: props.partSetup.actual_cycle_time,
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
        .put(update(props.partSetup.uuid).url);
};

const handleCancel = () => {
    router.visit(partSetups().url);
};
</script>

<template>
    <FormLayout
        :title="partSetupPage.form.editTitle"
        :description="
            partSetupPage.form.editDescription
        "
    >
        <PartSetupForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="
                partSetupPage.form
                    .editSubmitLabel
            "
            :current-image="props.partSetup.image"
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
