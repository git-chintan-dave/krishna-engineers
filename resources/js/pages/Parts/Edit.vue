<script setup lang="ts">
import {router, useForm} from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {partPage} from '@/config/parts';
import PartForm from '@/pages/Parts/components/PartForm.vue';
import {index as parts, update,} from '@/routes/parts';
import type {Part, PartFormData,} from '@/types/part';

const props = defineProps<{
    part: Part;
}>();

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('edit', {
            resource: partPage.title,
            resourceUrl: parts().url,
        }),
    },
});

const form = useForm<PartFormData>({
    name: props.part.name,
    new_part_name:
        props.part.new_part_name ?? '',
    section_id: props.part.section_id ?? null,
    image: null,
    remove_image: false,
    cutting_size:
        props.part.cutting_size ?? '',
    part_weight:
        props.part.part_weight ?? '',
    raw_material_weight:
        props.part.raw_material_weight ?? '',
    status: props.part.status === 'active',
});

const handleSubmit = () => {
    form
        .transform((data) => ({
            ...data,
            status: data.status
                ? 'active'
                : 'inactive',
            _method: 'PUT',
        }))
        .post(update(props.part.id).url);
};

const handleCancel = () => {
    router.visit(parts().url);
};
</script>

<template>
    <FormLayout
        :title="partPage.form.editTitle"
        :description="
            partPage.form.editDescription
        "
    >
        <PartForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="
                partPage.form.editSubmitLabel
            "
            :current-image="props.part.image"
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
