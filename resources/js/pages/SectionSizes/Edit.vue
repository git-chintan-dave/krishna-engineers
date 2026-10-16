<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { sectionSizePage } from '@/config/sectionSizes';
import SectionSizeForm from '@/pages/SectionSizes/components/SectionSizeForm.vue';
import {
    index as sectionSizes,
    update as updateSectionSize,
} from '@/routes/section-sizes';
import type { SectionSize, SectionSizeFormData } from '@/types/sectionSize';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    sectionSize: SectionSize;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('edit', {
            resource: sectionSizePage.title,
            resourceUrl: sectionSizes().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm<SectionSizeFormData>({
    section_id: props.sectionSize.section_id,
    size: props.sectionSize.size,
    weight: props.sectionSize.weight,
});

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form.put(updateSectionSize(props.sectionSize.uuid).url);
};

/*
|--------------------------------------------------------------------------
| Cancel
|--------------------------------------------------------------------------
*/

const handleCancel = () => {
    router.visit(sectionSizes().url);
};
</script>

<template>
    <FormLayout
        :title="sectionSizePage.form.editTitle"
        :description="sectionSizePage.form.editDescription"
    >
        <SectionSizeForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="sectionSizePage.form.editSubmitLabel"
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
