<script lang="ts" setup>
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { sectionSizePage } from '@/config/sectionSizes';
import SectionSizeForm from '@/pages/SectionSizes/components/SectionSizeForm.vue';
import { index as sectionSizes, store } from '@/routes/section-sizes';
import type { SectionSizeFormData } from '@/types/sectionSize';

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('create', {
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
    section_id: null,
    size: '',
    weight: '',
});

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form.post(store().url);
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
        :description="sectionSizePage.form.createDescription"
        :title="sectionSizePage.form.createTitle"
    >
        <SectionSizeForm
            :errors="form.errors"
            :form="form"
            :processing="form.processing"
            :submit-label="sectionSizePage.form.createSubmitLabel"
            @cancel="handleCancel"
            @submit="handleSubmit"
        />
    </FormLayout>
</template>
