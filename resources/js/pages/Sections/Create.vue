<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { sectionPage } from '@/config/sections';
import SectionForm from '@/pages/Sections/components/SectionForm.vue';
import { index as sections, store } from '@/routes/sections';
import type { SectionFormData } from '@/types/section';

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('create', {
            resource: sectionPage.title,
            resourceUrl: sections().url,
        }),
    },
});

const form = useForm<SectionFormData>({
    name: '',
    status: true,
});

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form.transform((data) => ({
        ...data,
        status: data.status ? 'active' : 'inactive',
    })).post(store().url);
};

/*
|--------------------------------------------------------------------------
| Cancel
|--------------------------------------------------------------------------
*/

const handleCancel = () => {
    router.visit(sections().url);
};
</script>

<template>
    <FormLayout
        :title="sectionPage.form.createTitle"
        :description="sectionPage.form.createDescription"
    >
        <SectionForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="sectionPage.form.createSubmitLabel"
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
